<?php

namespace App\Services;

use App\Helpers\XPHelper;
use App\Models\User;
use App\Models\Skill;
use App\Models\SkillUser;
use App\Models\UserProvider;
use App\Models\UserProviderSkill;
use Illuminate\Support\Facades\DB;

class SkillSyncService
{
    public function syncUserSkillsFromProvider(User $user, GitProviderInterface $api): bool
    {
        if (!$api->hasValidToken()) {
            return false;
        }

        $repositories = $api->getUserRepositories();
        if (empty($repositories)) {
            return false;
        }

        $languageStats = $this->getLanguageStatsFromRepos($api, $repositories);

        if (empty($languageStats)) {
            return false;
        }

        $this->updateUserSkills($user, $languageStats, $api->getProviderKey());
        return true;
    }

    private function getLanguageStatsFromRepos(GitProviderInterface $api, array $repositories): array
    {
        return collect($repositories)
            ->reject(fn($repo) => ($repo['fork'] ?? false) || ($repo['archived'] ?? false))
            ->map(function ($repo) use ($api) {
                $fullName = $repo['full_name']
                    ?? $repo['path_with_namespace']
                    ?? $repo['repo_full_name']
                    ?? null;

                if (!$fullName) {
                    return [
                        'repo'      => null,
                        'languages' => [],
                    ];
                }

                return [
                    'repo'      => $fullName,
                    'languages' => $api->getRepositoryLanguages($fullName),
                ];
            })
            ->reject(fn($repoData) => empty($repoData['languages']))
            ->flatMap(fn($repoData) => $repoData['languages'])
            ->groupBy(fn($bytes, $language) => $language)
            ->map(fn($bytesCollection) => $bytesCollection->sum())
            ->toArray();
    }

    private function updateUserSkills(User $user, array $languageStats, string $providerKey): void
    {

        $userProvider = $user->providers()->where('provider', $providerKey)->first();

        if (!$userProvider) {

            return;
        }

        // Get XP settings once per sync
        $baseXp = (int) DB::table('general_settings')->where('key', 'base_xp')->value('value') ?? 100;
        $bonusMultiplier = (float) DB::table('general_settings')->where('key', 'bonus_multiplier')->value('value') ?? 1.5;

        if ($baseXp === 0) {
            $baseXp = 100;
        }

        DB::transaction(function () use ($user, $userProvider, $languageStats, $baseXp, $bonusMultiplier) {
            collect($languageStats)->each(function ($bytes, $language) use ($userProvider, $baseXp, $bonusMultiplier) {
                $skillType = $this->getSkillType($language);

                $skill = Skill::firstOrCreate(
                    ['skill_name' => $language],
                    ['type' => $skillType, 'multiplier' => 1]
                );

                $initialXp = (int) round($baseXp * $bonusMultiplier * $skill->multiplier);

                UserProviderSkill::updateOrCreate(
                    [
                        'user_provider_id' => $userProvider->id,
                        'skill_id' => $skill->id,
                    ],
                    [
                        'xp' => $initialXp,
                    ]
                );
            });

            $aggregated = DB::table('user_provider_skills as ups')
                ->join('user_providers as up', 'up.id', '=', 'ups.user_provider_id')
                ->where('up.user_id', $user->id)
                ->select('ups.skill_id', DB::raw('SUM(ups.xp) as total_xp'))
                ->groupBy('ups.skill_id')
                ->get();

            foreach ($aggregated as $row) {
                $totalXp = (int) $row->total_xp;

                SkillUser::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'skill_id' => (int) $row->skill_id,
                    ],
                    [
                        'xp' => $totalXp,
                        'level' => XPHelper::calculateLevel($totalXp),
                    ]
                );
            }

            $user->load('skills');
            $userWithXP = XPHelper::getUserWithXP($user);
            $user->update(['xp' => $userWithXP['total_xp']]);

            XPHelper::clearCaches();
        });
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
                'Idris','Agda','Mercury','FoxPro','XQuery','RPG','ABAP','Inform','Pawn','Nemerle','LiveScript','HTML','CSS','SCSS',
                'SASS','Less','Stylus','PostCSS','Pug','Jade','Haml','Slim','Handlebars','Mustache','Twig','Liquid','Smarty',
                'Velocity','FreeMarker','Thymeleaf','JSP','ASP','ASPX','Razor','EJS','ERB','JSX','TSX','Svelte','Astro','MDX','MJML','AMP'
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
        return collect($categories)
            ->search(fn($languages) => in_array($language, $languages, true)) ?: 'other';
    }
}
