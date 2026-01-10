<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@300;400;500;600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    {{-- Trix Editor CSS & JS --}}
    <link rel="stylesheet" href="https://unpkg.com/trix@2.1.16/dist/trix.css">
    <script src="https://unpkg.com/trix@2.1.16/dist/trix.umd.min.js"></script>

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.10/dist/cdn.min.js"></script>

    <style>
        body { font-family: 'Alexandria', 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
        html[lang="en"] body { font-family: 'Outfit', 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
        [dir="rtl"] .rtl-flip { transform: scaleX(-1); }
        [dir="rtl"] .text-start { text-align: right; }
        [dir="ltr"] .text-start { text-align: left; }
        [dir="rtl"] .text-end { text-align: left; }
        [dir="ltr"] .text-end { text-align: right; }
        [dir="rtl"] .ps-4 { padding-right: 1rem; }
        [dir="ltr"] .ps-4 { padding-left: 1rem; }
        [dir="rtl"] .pe-4 { padding-left: 1rem; }
        [dir="ltr"] .pe-4 { padding-right: 1rem; }
        [x-cloak] { display: none !important; }
        @media (min-width: 1024px) {
            body.sidebar-mini .content-shell { padding-inline-start: 5rem !important; }
        }
        body.sidebar-mini #app-sidebar { width: 5rem; }
        body.sidebar-mini #app-sidebar .logo-text,
        body.sidebar-mini #app-sidebar .sidebar-label,
        body.sidebar-mini #app-sidebar .sidebar-heading { display: none; }
        body.sidebar-mini #app-sidebar .sidebar-link { justify-content: center; padding-left: 0.25rem; padding-right: 0.25rem; }
        body.sidebar-mini #app-sidebar .icon-slot { margin-inline-start: 0; margin-inline-end: 0; }
    </style>
    @stack('styles')
    @yield('head')
    <x-rich-text::styles theme="richtextlaravel" data-turbo-track="false" />
</head>
<body class="bg-gray-50 text-gray-900 leading-relaxed tracking-wide" x-data="{ sidebarOpen: false, sidebarMini: false }" :class="sidebarMini ? 'sidebar-mini' : ''">
    @include('layouts.partials.sidebar')

    <div class="flex flex-col min-h-screen content-shell {{ auth()->check() ? 'lg:ps-72' : '' }}">
        <nav class="bg-white/90 backdrop-blur border-b border-gray-200 shadow-sm py-4 px-4 sticky top-0 z-40">
            <div class="max-w-screen-2xl mx-auto flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    @auth
                        <button type="button"
                                class="inline-flex items-center justify-center w-10 h-10 rounded-full border border-gray-200 text-gray-700 hover:bg-gray-100 lg:hidden"
                                @click="sidebarOpen = true"
                                aria-label="{{ __('navigation.aria.open_sidebar') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>
                    @endauth

                    <a href="{{ route('home') }}" class="flex items-center gap-2" aria-label="{{ __('navigation.aria.go_to_dashboard') }}">
                       
                    </a>
                </div>

                @php
                    $notifCount = $notifCount
                        ?? (\App\Models\Installment::query()
                            ->whereIn('status', ['unpaid','partial'])
                            ->whereDate('due_date', now()->copy()->addDays(3))
                            ->count());
                @endphp

                <div class="flex items-center gap-3">
                    <form method="POST" action="{{ route('language.switch') }}" class="hidden sm:inline-flex items-center gap-1 text-xs font-semibold text-slate-600">
                        @csrf
                        <button type="submit" name="locale" value="ar" class="px-2 py-1 rounded-full {{ app()->isLocale('ar') ? 'bg-slate-900 text-white' : 'hover:text-slate-900' }}">{{ __('navigation.language.arabic') }}</button>
                        <span class="text-slate-300">|</span>
                        <button type="submit" name="locale" value="en" class="px-2 py-1 rounded-full {{ app()->isLocale('en') ? 'bg-slate-900 text-white' : 'hover:text-slate-900' }}">{{ __('navigation.language.english') }}</button>
                    </form>

                    @auth
                        <a href="{{ route('installments.notifications', ['today_only' => 1]) }}"
                           class="relative inline-flex items-center justify-center w-10 h-10 rounded-full border border-gray-200 hover:bg-gray-100"
                           title="{{ __('navigation.aria.notifications') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            @if($notifCount > 0)
                                <span class="absolute -top-1 -right-1 min-w-[20px] h-[20px] px-1 rounded-full bg-red-600 text-white text-[11px] leading-[20px] text-center">
                                    {{ $notifCount }}
                                </span>
                            @endif
                        </a>

                        <div class="flex items-center gap-2 text-sm text-gray-700">
                            <span class="inline-flex items-center gap-1">
                                {!! \App\Support\IconRegistry::svg('user', 'w-4 h-4') !!}
                                {{ Str::limit(Auth::user()->name, 24) }}
                            </span>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="text-sm text-gray-700 hover:text-black">{{ __('navigation.user_menu.login') }}</a>
                    @endauth
                </div>
            </div>
        </nav>

        <main class="flex-grow w-full px-6 py-9">
            @yield('content')
        </main>

        <footer class="bg-gray-900 text-yellow-400 py-4 text-center text-sm mt-8">
            © {{ date('Y') }} Viwe . {{ __('navigation.footer.rights') }}
        </footer>
    </div>

    @stack('scripts')

    <script>
    // Confirmation dialog for forms with data-confirm attribute
    document.addEventListener('DOMContentLoaded', function() {
        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (form.hasAttribute('data-confirm')) {
                e.preventDefault();

                const confirmType = form.getAttribute('data-confirm');
                const confirmTitle = form.getAttribute('data-confirm-title') || 'Are you sure?';
                const confirmMessage = form.getAttribute('data-confirm-message') || 'This action cannot be undone.';

                if (confirm(confirmTitle + '\n\n' + confirmMessage)) {
                    // Remove the data-confirm attribute to prevent infinite loop
                    form.removeAttribute('data-confirm');
                    form.submit();
                }
            }
        });
    });
    </script>
</body>
</html>
