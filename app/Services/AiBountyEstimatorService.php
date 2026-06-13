<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiBountyEstimatorService
{
    private const OPENAI_API_URL = 'https://api.openai.com/v1/chat/completions';

    private const MODEL = 'gpt-4o-mini';

    public function estimate(
        string $issueTitle,
        string $issueBody,
        array $languages = [],
        string $provider = 'github',
    ): array {
        $apiKey = config('services.openai.api_key');

        if (empty($apiKey)) {
            Log::warning('OpenAI API key not configured for AI bounty estimator.');
            throw new \RuntimeException('AI estimation unavailable: API key is not configured.');
        }

        $prompt = $this->buildPrompt($issueTitle, $issueBody, $languages, $provider);

        Log::info('AI bounty estimator: calling OpenAI API', [
            'model' => self::MODEL,
            'issue_title' => $issueTitle,
            'provider' => $provider,
            'languages' => array_keys($languages),
        ]);

        try {
            $response = Http::withToken($apiKey)
                ->timeout(20)
                ->post(self::OPENAI_API_URL, [
                    'model' => self::MODEL,
                    'temperature' => 0.5,
                    'max_tokens' => 400,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $this->systemPrompt(),
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'response_format' => ['type' => 'json_object'],
                ]);

            if ($response->failed()) {
                Log::warning('AI bounty estimator: OpenAI API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \RuntimeException('AI estimation failed (HTTP '.$response->status().'). Please try again later.');
            }

            $content = $response->json('choices.0.message.content');

            Log::info('AI bounty estimator: received response', [
                'status' => $response->status(),
                'raw_content' => $content,
                'usage' => $response->json('usage'),
            ]);

            return $this->parseResponse($content);

        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('AI bounty estimator: unexpected exception', [
                'message' => $e->getMessage(),
                'class' => get_class($e),
            ]);
            throw new \RuntimeException('AI estimation failed. Please try again later.');
        }
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are an expert software engineering advisor that estimates the difficulty and effort of GitHub/GitLab issues.

Your task: analyze an issue and suggest a precise XP reward for a developer bounty platform. XP ranges from 1 to 1000.

Complexity categories and typical XP values (these are guides, not formulas — always reason from the actual issue):
- trivial: 1–50 XP  — e.g., typo fix (5), rename a variable (10), update a config value (20), minor CSS tweak (35)
- easy:    51–150 XP — e.g., fix a one-liner bug (55), add a missing null-check (70), simple form field addition (90), basic REST endpoint fix (110), small API integration (140)
- medium:  151–300 XP — e.g., new UI component (160), moderate bug with several edge cases (200), API integration with error handling (240), local refactor touching 3–5 files (280)
- hard:    301–500 XP — e.g., new complex feature (320), security vulnerability fix (380), performance optimization with profiling (430), architectural change (470)
- expert:  501–1000 XP — e.g., major system redesign (550), framework migration (650), cryptographic implementation (800), highly complex algorithm (950)

Key factors to weigh:
1. Cyclomatic complexity: How many decision branches, loops, or error paths must the developer handle? More paths → higher XP.
2. Language difficulty: Assembly/Rust/C++ > Java/C#/Go > Python/JS/TS > HTML/CSS/YAML.
3. Scope: How many files/systems are likely touched? Isolated fix vs. cross-cutting concern.
4. Risk: Could a mistake break production? Security/data-integrity issues warrant more XP.

IMPORTANT: Do NOT always pick the middle of a range. Reason about the specific issue and choose a precise value.
- A trivial one-line bug fix in JS should be ~60–70, not 100.
- A complex Java bug touching multiple classes should be ~130–140, not 100.
- A simple Python script fix should be lower than a similar fix in C++.

Respond ONLY with valid JSON in exactly this structure:
{
  "min": <integer 1-1000>,
  "max": <integer 1-1000>,
  "suggested": <integer between min and max>,
  "complexity": "<one of: trivial|easy|medium|hard|expert>",
  "reasoning": "<1-2 sentence explanation in English>"
}
PROMPT;
    }

    private function buildPrompt(
        string $issueTitle,
        string $issueBody,
        array $languages,
        string $provider,
    ): string {
        $languageList = empty($languages)
            ? 'Unknown'
            : implode(', ', array_keys(array_slice($languages, 0, 5, true)));

        $bodyPreview = mb_substr(strip_tags($issueBody), 0, 800);

        return <<<PROMPT
ISSUE TITLE: {$issueTitle}

PROVIDER: {$provider}
REPOSITORY LANGUAGES: {$languageList}

ISSUE DESCRIPTION:
{$bodyPreview}

Estimate the XP reward for resolving this issue. Consider:
1. Cyclomatic complexity (number of decision points likely needed)
2. Programming language difficulty
3. Scope of changes implied by the issue
PROMPT;
    }

    private function parseResponse(?string $content): array
    {
        if (empty($content)) {
            throw new \RuntimeException('AI returned an empty response. Please try again later.');
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            $min = max(1, min(1000, (int) ($data['min'] ?? 10)));
            $max = max(1, min(1000, (int) ($data['max'] ?? 100)));
            $suggested = max($min, min($max, (int) ($data['suggested'] ?? (int) (($min + $max) / 2))));

            if ($min >= $max) {
                $max = min(1000, $min + 20);
            }

            return [
                'min' => $min,
                'max' => $max,
                'suggested' => $suggested,
                'complexity' => $data['complexity'] ?? 'medium',
                'reasoning' => $data['reasoning'] ?? '',
            ];
        } catch (\JsonException $e) {
            Log::warning('Failed to parse OpenAI bounty estimate JSON', ['content' => $content]);
            throw new \RuntimeException('Failed to process AI response. Please try again later.');
        }
    }
}
