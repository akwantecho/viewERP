@auth
@php
    $navigation = config('navigation', []);
    $dir = app()->getLocale() === 'ar' ? 'rtl' : 'ltr';
    $isRtl = $dir === 'rtl';
    $borderClass = $isRtl ? 'border-r-4' : 'border-l-4';
    $flexDir = $isRtl ? 'flex-row-reverse' : 'flex-row';
    $textAlign = $isRtl ? 'text-right' : 'text-left';

    $canDisplay = function (array $item): bool {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        if (!empty($item['super_only']) && !$user->is_super) {
            return false;
        }
        if (!empty($item['perm'])) {
            return $user->can($item['perm']);
        }
        return true;
    };

    $resolveUrl = function (?string $routeName) {
        if ($routeName && \Illuminate\Support\Facades\Route::has($routeName)) {
            try {
                return route($routeName);
            } catch (\Throwable $e) {
                return '#';
            }
        }
        return '#';
    };
@endphp

<div x-cloak>
    <div
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-40 lg:hidden"
        x-show="sidebarOpen"
        x-transition.opacity
        @click="sidebarOpen = false"
        aria-hidden="true"
    ></div>

    <aside
        id="app-sidebar"
        class="fixed inset-y-0 {{ $isRtl ? 'right-0' : 'left-0' }} z-50 w-72 max-w-full bg-white border-{{ $isRtl ? 'l' : 'r' }} border-slate-100 shadow-xl flex flex-col overflow-y-auto"
        :class="sidebarOpen ? 'translate-x-0' : '{{ $isRtl ? 'translate-x-full' : '-translate-x-full' }} lg:translate-x-0'"
        x-transition:enter="transition transform duration-300"
        x-transition:enter-start="{{ $isRtl ? 'translate-x-full' : '-translate-x-full' }}"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition transform duration-300"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="{{ $isRtl ? 'translate-x-full' : '-translate-x-full' }}"
        role="navigation"
        aria-label="{{ __('navigation.aria.main_navigation') }}"
    >
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <img src="{{ asset('images/Vlogo.png') }}" alt="Logo" class="w-10 h-10 object-contain" loading="lazy">
                <div class="logo-text">
                    <p class="font-semibold text-slate-900 text-sm">{{ config('app.name', 'Viwe ERP') }}</p>
                    <p class="text-xs text-slate-500">{{ __('navigation.badges.management_console') }}</p>
                </div>
            </div>
            <button
                type="button"
                class="lg:hidden inline-flex items-center justify-center w-10 h-10 rounded-full border border-slate-200 text-slate-600 hover:bg-slate-50"
                @click="sidebarOpen = false"
                aria-label="{{ __('navigation.aria.close_sidebar') }}"
            >
                {!! \App\Support\IconRegistry::svg('x', 'w-4 h-4') !!}
            </button>
        </div>

        <nav class="flex-1 px-3 py-4" dir="{{ $dir }}">
            @foreach($navigation as $entry)
                @if(isset($entry['heading']))
                    @php
                        $children = collect($entry['children'] ?? [])
                            ->filter(fn ($child) => $canDisplay($child))
                            ->values();
                    @endphp
                    @if($children->isNotEmpty())
                        <div class="mt-6">
                            <p class="sidebar-heading text-xs font-semibold uppercase tracking-wider text-slate-400 {{ $textAlign }}">
                                {{ __($entry['heading']) }}
                            </p>
                            <div class="mt-3 space-y-1">
                                @foreach($children as $child)
                                    @php
                                        $isActive = !empty($child['route']) && request()->routeIs($child['route'] . '*');
                                        $url = $resolveUrl($child['route'] ?? null);
                                    @endphp
                                    <a
                                        href="{{ $url }}"
                                        class="sidebar-link group flex items-center gap-3 rounded-2xl px-3 py-2 text-sm font-medium transition border-l-4 border-r-4 {{ $isActive ? 'bg-emerald-50 text-emerald-800 ' . ($isRtl ? 'border-r-emerald-500 border-l-transparent' : 'border-l-emerald-500 border-r-transparent') : 'text-slate-600 hover:bg-slate-50 border-transparent' }}"
                                        @if($isActive) aria-current="page" @endif
                                    >
                                        <span class="icon-slot text-emerald-600 {{ $isRtl ? 'ms-2' : 'me-2' }}">
                                            {!! \App\Support\IconRegistry::svg($child['icon'] ?? 'default', 'w-5 h-5') !!}
                                        </span>
                                        <span class="sidebar-label flex-1 {{ $textAlign }}">{{ __($child['label']) }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @else
                    @if($canDisplay($entry))
                        @php
                            $isActive = !empty($entry['route']) && request()->routeIs($entry['route'] . '*');
                            $url = $resolveUrl($entry['route'] ?? null);
                        @endphp
                        <a
                            href="{{ $url }}"
                            class="sidebar-link group flex items-center gap-3 rounded-2xl px-3 py-3 text-sm font-semibold transition border-l-4 border-r-4 {{ $isActive ? 'bg-emerald-100 text-emerald-900 ' . ($isRtl ? 'border-r-emerald-500 border-l-transparent' : 'border-l-emerald-500 border-r-transparent') : 'text-slate-700 hover:bg-slate-100 border-transparent' }}"
                            @if($isActive) aria-current="page" @endif
                        >
                            <span class="icon-slot text-emerald-600 {{ $isRtl ? 'ms-2' : 'me-2' }}">
                                {!! \App\Support\IconRegistry::svg($entry['icon'] ?? 'default', 'w-5 h-5') !!}
                            </span>
                            <span class="sidebar-label flex-1 {{ $textAlign }}">{{ __($entry['label']) }}</span>
                        </a>
                    @endif
                @endif
            @endforeach
        </nav>

        <div class="border-t border-slate-100 px-4 py-5">
            <p class="sidebar-heading text-xs font-semibold uppercase tracking-wider text-slate-400 {{ $textAlign }}">{{ __('navigation.user_menu.profile') }}</p>
            <div class="mt-3 space-y-2">
                <div class="flex items-center gap-3 text-sm text-slate-600">
                    <span class="icon-slot text-emerald-600 {{ $isRtl ? 'ms-2' : 'me-2' }}">
                        {!! \App\Support\IconRegistry::svg('user', 'w-5 h-5') !!}
                    </span>
                    <span class="sidebar-label font-semibold">{{ Str::limit(Auth::user()->name, 24) }}</span>
                </div>
                <a href="{{ route('profile.index') }}"
                   class="sidebar-link flex items-center gap-3 rounded-2xl px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
                    <span class="icon-slot text-emerald-600 {{ $isRtl ? 'ms-2' : 'me-2' }}">
                        {!! \App\Support\IconRegistry::svg('settings', 'w-5 h-5') !!}
                    </span>
                    <span class="sidebar-label">{{ __('navigation.user_menu.profile') }}</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="sidebar-link flex w-full items-center gap-3 rounded-2xl px-3 py-2 text-sm font-semibold text-red-600 border border-red-200 hover:bg-red-50">
                        <span class="icon-slot {{ $isRtl ? 'ms-2' : 'me-2' }}">
                            {!! \App\Support\IconRegistry::svg('logout', 'w-5 h-5') !!}
                        </span>
                        <span class="sidebar-label">{{ __('navigation.user_menu.logout') }}</span>
                    </button>
                </form>
            </div>
        </div>
    </aside>
</div>
@endauth
