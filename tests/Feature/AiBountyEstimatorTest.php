<?php

use App\Models\User;
use App\Services\AiBountyEstimatorService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('returns a 503 when the openai api key is not configured', function () {
    config(['services.openai.api_key' => null]);

    $this->actingAs($this->user)
        ->postJson('/api/bounties/estimate-xp', [
            'issue_title' => 'Fix: button not rendering',
        ])
        ->assertStatus(503)
        ->assertJsonFragment(['message' => 'AI estimation unavailable: API key is not configured.']);
});

it('returns a valid xp estimate from the openai api', function () {
    config(['services.openai.api_key' => 'test-key']);

    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => json_encode([
                        'min' => 55,
                        'max' => 120,
                        'suggested' => 75,
                        'complexity' => 'easy',
                        'reasoning' => 'Simple one-line bug fix in JavaScript.',
                    ]),
                ],
            ]],
        ], 200),
    ]);

    $this->actingAs($this->user)
        ->postJson('/api/bounties/estimate-xp', [
            'issue_title' => 'Fix: button not rendering',
            'issue_body' => 'The submit button is missing on mobile.',
            'provider' => 'github',
        ])
        ->assertOk()
        ->assertJson([
            'min' => 55,
            'max' => 120,
            'suggested' => 75,
            'complexity' => 'easy',
        ]);
});

it('clamps xp values to the 1-1000 range', function () {
    config(['services.openai.api_key' => 'test-key']);

    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => json_encode([
                        'min' => -50,
                        'max' => 9999,
                        'suggested' => 5000,
                        'complexity' => 'expert',
                        'reasoning' => 'Extreme values should be clamped.',
                    ]),
                ],
            ]],
        ], 200),
    ]);

    $response = $this->actingAs($this->user)
        ->postJson('/api/bounties/estimate-xp', [
            'issue_title' => 'Major redesign',
        ])
        ->assertOk()
        ->json();

    expect($response['min'])->toBeGreaterThanOrEqual(1);
    expect($response['max'])->toBeLessThanOrEqual(1000);
    expect($response['suggested'])->toBeGreaterThanOrEqual($response['min'])
        ->and($response['suggested'])->toBeLessThanOrEqual($response['max']);
});

it('returns a 503 when openai responds with a non-200 status', function () {
    config(['services.openai.api_key' => 'test-key']);

    Http::fake([
        'api.openai.com/*' => Http::response(['error' => 'rate limit'], 429),
    ]);

    $this->actingAs($this->user)
        ->postJson('/api/bounties/estimate-xp', [
            'issue_title' => 'Fix something',
        ])
        ->assertStatus(503);
});

it('requires issue_title', function () {
    config(['services.openai.api_key' => 'test-key']);

    $this->actingAs($this->user)
        ->postJson('/api/bounties/estimate-xp', [])
        ->assertUnprocessable();
});

it('parses response with varied suggested values correctly', function (int $suggested, int $min, int $max) {
    config(['services.openai.api_key' => 'test-key']);

    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => json_encode([
                        'min' => $min,
                        'max' => $max,
                        'suggested' => $suggested,
                        'complexity' => 'medium',
                        'reasoning' => 'Test.',
                    ]),
                ],
            ]],
        ], 200),
    ]);

    $result = $this->actingAs($this->user)
        ->postJson('/api/bounties/estimate-xp', ['issue_title' => 'Test issue'])
        ->assertOk()
        ->json();

    expect($result['suggested'])->toBe($suggested);
})->with([
    'low easy' => [65,  51,  120],
    'mid medium' => [220, 151, 300],
    'high hard' => [450, 301, 500],
    'expert range' => [750, 501, 1000],
]);

describe('AiBountyEstimatorService', function () {
    it('throws when api key is missing', function () {
        config(['services.openai.api_key' => '']);

        $service = new AiBountyEstimatorService;

        expect(fn () => $service->estimate('Fix bug', ''))->toThrow(\RuntimeException::class, 'API key is not configured');
    });
});
