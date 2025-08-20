<?php

namespace App\Services;

use App\Models\User;
use App\Models\Skill;
use App\Models\SkillUser;

class GitHubSkillSyncService
{
    public function __construct(
        private GitHubApiService $githubApi
    ) {}

    public function syncUserSkillsFromGitHub(User $user): bool
    {
        try {
            $this->githubApi = new GitHubApiService($user);
            if (!$this->githubApi->hasValidToken()) {
                return false;
            }

            $repositories = $this->githubApi->getUserRepositories();
            if (empty($repositories)) {
                return false;
            }

            $languageStats = $this->getLanguageStatsFromRepos($repositories);

            if (empty($languageStats)) {
                return false;
            }

            $this->updateUserSkills($user, $languageStats);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getLanguageStatsFromRepos(array $repositories): array
    {
        return collect($repositories)
            ->reject(fn($repo) => $repo['fork'] || $repo['archived'])
            ->map(fn($repo) => [
                'repo' => $repo['full_name'],
                'languages' => $this->githubApi->getRepositoryLanguages($repo['full_name'])
            ])
            ->reject(fn($repoData) => empty($repoData['languages']))
            ->tap(function ($repoCollection) {
                $repoCollection->each(function ($repo) {
                });
            })
            ->flatMap(fn($repoData) => $repoData['languages'])
            ->groupBy(fn($bytes, $language) => $language)
            ->map(fn($bytesCollection) => $bytesCollection->sum())
            ->toArray();
    }

    private function updateUserSkills(User $user, array $languageStats): void
    {
        collect($languageStats)
            ->each(function ($bytes, $language) use ($user) {
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
