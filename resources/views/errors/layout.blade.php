<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    @vite('resources/css/errors.css')
    <title>Document</title>
</head>
<body class="h-full">
<div
    class="grid min-h-full grid-cols-1 grid-rows-[1fr_auto_1fr] bg-white lg:grid-cols-[max(50%,36rem)_1fr] dark:bg-gray-900">
    <header class="mx-auto w-full max-w-7xl px-6 pt-6 sm:pt-10 lg:col-span-2 lg:col-start-1 lg:row-start-1 lg:px-8">
        <a href="#">
            <span class="sr-only">GitCodeGud</span>
            <img src="http://127.0.0.1:5173/resources/assets/title.svg" alt="Logo title"
                 class="h-10 w-auto sm:h-12">
        </a>
    </header>
    <main class="mx-auto w-full max-w-7xl px-6 py-24 sm:py-32 lg:col-span-2 lg:col-start-1 lg:row-start-2 lg:px-8">
        <div class="max-w-lg">
            <p class="text-base/8 font-semibold text-indigo-600 dark:text-indigo-400">@yield('code')</p>
            <h1 class="mt-4 text-5xl font-semibold tracking-tight text-pretty text-gray-900 sm:text-6xl dark:text-white">
                @yield('title')</h1>
            <p class="mt-6 text-lg font-medium text-pretty text-gray-500 sm:text-xl/8 dark:text-gray-400">@yield('message')</p>
            <div class="mt-10">
                <a href="/dashboard" class="text-sm/7 font-semibold text-indigo-600 dark:text-indigo-400"><span
                        aria-hidden="true">&larr;</span> Back to home</a>
            </div>
        </div>
    </main>

    <div class="hidden lg:relative lg:col-start-2 lg:row-start-1 lg:row-end-4 lg:block">
        <img
            src="/assets/images/not-found-dark.png"
            alt="" class="absolute inset-0 size-full object-cover not-dark:hidden" />
        <img
            src="/assets/images/not-found.png"
            alt="" class="absolute inset-0 size-full object-cover dark:hidden" />
    </div>
</div>
</body>
</html>
