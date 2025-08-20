<?php

namespace App\Services;

use App\Models\User;
use App\Models\Skill;
use App\Models\SkillUser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubSkillSyncService
{
    public function syncUserSkillsFromGitHub(User $user): bool
    {
        try {
            $repositories = $this->getUserRepositories($user);
            if (empty($repositories)) {
                return false;
            }

            $languageStats = $this->getLanguageStatsFromRepos($user, $repositories);
            $this->updateUserSkills($user, $languageStats);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    private function getUserRepositories(User $user): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $user->oauth_provider_token,
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'GitCodeGud-App'
        ])->get('https://api.github.com/user/repos', [
            'type' => 'owner',
            'sort' => 'updated',
            'per_page' => 100
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to fetch GitHub repositories: ' . $response->status());
        }

        return $response->json();
    }
    private function getLanguageStatsFromRepos(User $user, array $repositories): array
    {
        $totalLanguageBytes = [];

        foreach ($repositories as $repo) {
            if ($repo['fork'] || $repo['archived']) {
                continue;
            }

            $languages = $this->getRepositoryLanguages($user, $repo['full_name']);

            foreach ($languages as $language => $bytes) {
                $totalLanguageBytes[$language] = ($totalLanguageBytes[$language] ?? 0) + $bytes;
            }
        }

        return $totalLanguageBytes;
    }

    private function getRepositoryLanguages(User $user, string $repoFullName): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $user->oauth_provider_token,
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'GitCodeGud-App'
        ])->get("https://api.github.com/repos/{$repoFullName}/languages");

        if ($response->failed()) {
            return [];
        }

        return $response->json();
    }
    
    private function updateUserSkills(User $user, array $languageStats): void
    {
        foreach ($languageStats as $language => $bytes) {
            $skillType = $this->getSkillType($language);

            $skill = Skill::firstOrCreate([
                'skill_name' => $language
            ], [
                'type' => $skillType
            ]);

            SkillUser::firstOrCreate([
                'user_id' => $user->id,
                'skill_id' => $skill->id,
            ], [
                'xp' => 1,
                'level' => 1,
            ]);
        }
    }
    private function getSkillType(string $language): string
    {
        $language = trim($language);

        $categories = [
            'language' => [
                'PHP','JavaScript','TypeScript','Python','Java','C','C++','C#','Go','Rust','Ruby','Swift','Kotlin','Dart','Scala','R',
                'MATLAB','Octave','Perl','Lua','Haskell','Erlang','Elixir','F#','Objective-C','Objective-C++','Groovy','Julia','Nim',
                'Zig','Crystal','Fortran','COBOL','Ada','Pascal','Delphi','Assembly','OCaml','ReasonML','Clojure','Common Lisp','Scheme',
                'Prolog','Visual Basic','VBA','VB.NET','Hack','Solidity','VHDL','Verilog','OpenCL','CUDA','GLSL','ShaderLab','GDScript',
                'Q#','Tcl','Smalltalk','APL','Forth','Racket','Elm','PureScript','CoffeeScript','Awk','Sed','Gnuplot','Roff','Pony',
                'Idris','Agda','Mercury','FoxPro','XQuery','RPG','ABAP','Inform','Pawn','Nemerle','LiveScript'
            ],

            'framework' => [
                'React','Next.js','Angular','Vue','Nuxt.js','Svelte','SvelteKit','SolidJS','Astro',
                'Laravel','Symfony','CodeIgniter','Yii','CakePHP','Slim','Lumen',
                'Django','Flask','FastAPI','Tornado','Bottle','Pyramid',
                'Spring','Spring Boot','Micronaut','Quarkus','Play','Vert.x','Dropwizard',
                'Ruby on Rails','Sinatra','Hanami',
                '.NET','ASP.NET','ASP.NET Core',
                'Express','NestJS','Hapi','Koa','AdonisJS','Sails.js','FeathersJS','LoopBack',
                'React Native','Expo','Ionic','Cordova','Capacitor','Flutter','Qt','Kivy','Electron','Tauri','SwiftUI','Jetpack Compose',
                'Gatsby','Gridsome','Remix','Blitz.js','RedwoodJS','Meteor'
            ],

            'tool' => [
                'Shell','PowerShell','Batchfile','Makefile','CMake','Meson','Bazel','Buck','Ninja',
                'Gradle','Maven','Ant','SBT','Poetry','Pipenv','Setuptools','Conda','Virtualenv',
                'Dockerfile','Docker Compose','Kubernetes','Helm','Kustomize','Ansible','Puppet','Chef','SaltStack',
                'Vagrant','Packer','Nix','NixOS','HCL','Terraform',
                'pnpm','Yarn','npm','Lerna','Nx','Rush','TurboRepo',
                'OpenSCAD','Mathematica','Maple','Wolfram','Starlark','Fish','Zsh','Bash','Esbuild','SWC','Rollup','Webpack',
                'Parcel','Gulp','Grunt','Snowpack','Rspack'
            ],

            'database' => [
                'SQL','MySQL','T-SQL','PLpgSQL','PLSQL','SQLPL','SQLite','MariaDB','PostgreSQL','Oracle',
                'MongoDB','Redis','Cassandra','CouchDB','DynamoDB','Elasticsearch','Solr','Neo4j','Gremlin','InfluxDB','ClickHouse',
                'Firestore','Realm','HBase','RethinkDB','ArangoDB','FaunaDB','DuckDB','Trino','Presto','Snowflake','BigQuery',
                'GraphQL','Apollo','Prisma','Hasura'
            ],
        ];

        foreach ($categories as $type => $list) {
            if (in_array($language, $list, true)) {
                return $type;
            }
        }

        return 'other';
    }
}
