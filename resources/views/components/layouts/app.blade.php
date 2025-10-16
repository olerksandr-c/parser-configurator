<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        {{-- ВИПРАВЛЕНО: 'utf-8' замість 'utf-g' --}}
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? 'Парсер-Конфігуратор' }}</title>

        {{-- 
            Підключаємо стилі Tailwind CSS через CDN.
            Це найпростіший спосіб отримати стилі без встановлення Breeze/npm.
        --}}
        <script src="https://cdn.tailwindcss.com"></script>

        {{-- Директива для стилів Livewire --}}
        @livewireStyles
    </head>
    <body class="bg-gray-100">

        <main class="container mx-auto mt-10">
            {{-- Сюди Livewire буде вставляти вміст вашого компонента --}}
            {{ $slot }}
        </main>

        {{-- Директива для скриптів Livewire --}}
        @livewireScripts
    </body>
</html>