<!DOCTYPE html>
@php($themeScope = \App\Support\ThemeScope::for($page['component'], auth()->user()))
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme-scope="{{ $themeScope }}" @class(['dark' => $themeScope === 'app' && ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately.
             Only the dashboard has dark mode; the public site stays light. --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (document.documentElement.dataset.themeScope === 'app' && appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        {{-- Brand colours picked in Admin → Pengaturan Situs; must come after app.css.
             app.ts keeps this element in sync on client-side navigation. --}}
        <style id="brand-palette">{!! \App\Models\SiteSetting::paletteCss() ?? '' !!}</style>

        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
