<?php

namespace Database\Factories;

use App\Models\Bounty;
use App\Models\Issue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bounty>
 */
class BountyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $allLanguages = array_merge(
            ['PHP','JavaScript','TypeScript','Python','Java','C','C++','C#','Go','Rust','Ruby','Swift','Kotlin','Dart','Scala','R',
                'MATLAB','Octave','Perl','Lua','Haskell','Erlang','Elixir','F#','Objective-C','Objective-C++','Groovy','Julia','Nim',
                'Zig','Crystal','Fortran','COBOL','Ada','Pascal','Delphi','Assembly','OCaml','ReasonML','Clojure','Common Lisp','Scheme',
                'Prolog','Visual Basic','VBA','VB.NET','Hack','Solidity','VHDL','Verilog','OpenCL','CUDA','GLSL','ShaderLab','GDScript',
                'Q#','Tcl','Smalltalk','APL','Forth','Racket','Elm','PureScript','CoffeeScript','Awk','Sed','Gnuplot','Roff','Pony',
                'Idris','Agda','Mercury','FoxPro','XQuery','RPG','ABAP','Inform','Pawn','Nemerle','LiveScript','HTML','CSS','SCSS',
                'SASS','Less','Stylus','PostCSS','Pug','Jade','Haml','Slim','Handlebars','Mustache','Twig','Liquid','Smarty',
                'Velocity','FreeMarker','Thymeleaf','JSP','ASP','ASPX','Razor','EJS','ERB','JSX','TSX','Svelte','Astro','MDX','MJML','AMP'],

            ['React','Next.js','Angular','Vue','Nuxt.js','Svelte','SvelteKit','SolidJS','Astro',
                'Laravel','Symfony','CodeIgniter','Yii','CakePHP','Slim','Lumen',
                'Django','Flask','FastAPI','Tornado','Bottle','Pyramid',
                'Spring','Spring Boot','Micronaut','Quarkus','Play','Vert.x','Dropwizard',
                'Ruby on Rails','Sinatra','Hanami',
                '.NET','ASP.NET','ASP.NET Core',
                'Express','NestJS','Hapi','Koa','AdonisJS','Sails.js','FeathersJS','LoopBack',
                'React Native','Expo','Ionic','Cordova','Capacitor','Flutter','Qt','Kivy','Electron','Tauri','SwiftUI','Jetpack Compose',
                'Gatsby','Gridsome','Remix','Blitz.js','RedwoodJS','Meteor'],

            ['SQL','MySQL','T-SQL','PLpgSQL','PLSQL','SQLPL','SQLite','MariaDB','PostgreSQL','Oracle',
                'MongoDB','Redis','Cassandra','CouchDB','DynamoDB','Elasticsearch','Solr','Neo4j','Gremlin','InfluxDB','ClickHouse',
                'Firestore','Realm','HBase','RethinkDB','ArangoDB','FaunaDB','DuckDB','Trino','Presto','Snowflake','BigQuery',
                'GraphQL','Apollo','Prisma','Hasura'],

            ['Shell','PowerShell','Dockerfile','Kubernetes','Terraform','npm','Webpack','Gradle','Maven']
        );

        $availableLanguages = array_intersect($allLanguages, [
            'PHP','JavaScript','TypeScript','Python','Java','C++','C#','Go','Rust','Ruby','Swift','Kotlin',
            'React','Next.js','Angular','Vue','Laravel','Symfony','Django','Flask','Spring','Express',
            'SQL','MySQL','PostgreSQL','MongoDB','Redis','HTML','CSS','SCSS'
        ]);

        $selectedLanguages = $this->faker->randomElements(
            $availableLanguages,
            $this->faker->numberBetween(1, 3)
        );

        return [
            'issue_id' => Issue::factory(),
            'status' => $this->faker->randomElement(['open', 'closed']),
            'title' => 'Fix ' . $this->faker->word() . ' ' . $this->faker->word(),
            'description' => $this->faker->sentence(),
            'reward_xp' => $this->faker->numberBetween(5, 100),
            'languages' => $selectedLanguages,
            ];
    }
}
