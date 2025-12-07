<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('auth.login.title') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@300;400;500;600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- ✅ Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#626569',
                        dark: '#f5ce00',
                        light: '#9bdf79',
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Alexandria', 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
        html[lang="en"] body { font-family: 'Outfit', 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 antialiased">
    <div class="flex min-h-screen">

        <!-- Side Image -->
        <div class="hidden md:flex md:w-1/2 bg-primary items-center justify-center">
            <img src="{{ asset('images/ViewBranding.jpg') }}" alt="Real Estate" class="w-full h-full object-cover opacity-90">
        </div>

        <!-- Login Form -->
        <div class="w-full md:w-1/2 flex items-center justify-center bg-white p-8">
            <div class="w-full max-w-md space-y-6">
                @php
                    $availableLocales = [
                        'en' => __('auth.language.english'),
                        'ar' => __('auth.language.arabic'),
                    ];
                    $currentLocale = app()->getLocale();
                @endphp

                <img src="{{ asset('images/Vlogo.png') }}" alt="Logo" class="w-20 h-20 object-contain mx-auto">

                <div class="flex justify-center">
                    <span class="sr-only">{{ __('auth.language.label') }}</span>
                    <form method="POST" action="{{ route('language.switch') }}" class="inline-flex rounded-full border border-gray-200 bg-gray-50 p-1 text-xs font-medium">
                        @csrf
                        @foreach ($availableLocales as $locale => $label)
                            <button type="submit" name="locale" value="{{ $locale }}" class="px-3 py-1 rounded-full transition {{ $currentLocale === $locale ? 'bg-primary text-white' : 'text-gray-600 hover:text-primary' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </form>
                </div>

                <h2 class="text-3xl font-bold text-primary text-center mb-6">{{ __('auth.login.title') }}</h2>
                <p class="text-center text-sm text-gray-500">{{ __('auth.login.subtitle') }}</p>

                @if ($errors->any())
                    <div class="bg-red-100 text-red-700 p-3 rounded text-sm text-center">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.submit') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-700">{{ __('auth.login.email') }}</label>
                        <input type="email" name="email" required autofocus
                            class="w-full border border-gray-300 px-4 py-2 rounded-md focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-700">{{ __('auth.login.password') }}</label>
                        <input type="password" name="password" required
                            class="w-full border border-gray-300 px-4 py-2 rounded-md focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <label class="flex items-center gap-1">
                            <input type="checkbox" name="remember" class="rounded text-primary">
                            {{ __('auth.login.remember') }}
                        </label>
                    </div>

                    <button type="submit"
                        class="w-full bg-primary hover:bg-dark text-white py-2 rounded-md transition">
                        {{ __('auth.login.button') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
