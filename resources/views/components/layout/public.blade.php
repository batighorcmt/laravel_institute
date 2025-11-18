<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? ($school->name ?? config('app.name', 'Admission System')) }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <!-- Kalpurush Bangla font -->
        <link rel="stylesheet" href="https://fonts.maateen.me/kalpurush/font.css">
        <!-- Admin panel assets for unified UI -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('styles')
        <style>
            body { font-family: 'Kalpurush', 'Noto Sans Bengali', 'Hind Siliguri', system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; }
        </style>
    </head>
    <body>
        <div>
            
            <div>
                {{ $slot }}
            </div>
        </div>
        @php($flash = session()->get('success') ?? session()->get('status') ?? null)
        @if($flash)
            @push('scripts')
            <script>
                window.addEventListener('DOMContentLoaded', function(){
                    if (window.toastr) { toastr.success(@json($flash)); }
                });
            </script>
            @endpush
        @endif
        @if(session('error'))
            @push('scripts')
            <script>
                window.addEventListener('DOMContentLoaded', function(){
                    if (window.toastr) { toastr.error(@json(session('error'))); }
                });
            </script>
            @endpush
        @endif
        @stack('scripts')
    </body>
</html>
