<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="fi">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />

        <title>{{ config('app.name') }}</title>

        <style>
            [x-cloak] { display: none !important; }
        </style>

        @filamentStyles
        @vite('resources/css/app.css')
    </head>

    <body class="fi-body antialiased bg-gray-50 text-gray-950 dark:bg-gray-950 dark:text-white">
        {{ $slot }}

        @filamentScripts
        @vite('resources/js/app.js')

        @livewire('ai-panel')
    </body>
</html>