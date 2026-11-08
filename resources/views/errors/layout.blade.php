<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — ASRI Boarding House</title>
    @vite(['resources/css/public.css', 'resources/js/public.js'])
</head>
<body class="min-h-screen bg-cream-50 font-sans text-ink-900 flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-white rounded-3xl border border-line-200 p-8 shadow-sm text-center">
        <div class="h-16 w-16 rounded-2xl bg-forest-50 text-forest-800 flex items-center justify-center mx-auto mb-6 text-2xl font-black shadow-inner">
            @yield('icon', '⚠️')
        </div>

        <span class="num text-4xl sm:text-5xl font-black text-forest-900 block mb-2">
            @yield('code')
        </span>

        <h1 class="text-xl font-bold text-forest-900 mb-2">
            @yield('title')
        </h1>

        <p class="text-xs sm:text-sm text-ink-600 mb-8 leading-relaxed">
            @yield('message')
        </p>

        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a
                href="{{ url('/') }}"
                class="px-5 py-2.5 rounded-xl bg-forest-900 hover:bg-forest-800 text-white font-bold text-xs shadow-xs transition"
            >
                Kembali ke Beranda
            </a>
            <button
                type="button"
                onclick="window.history.back()"
                class="px-5 py-2.5 rounded-xl border border-line-200 bg-white hover:bg-slate-50 text-ink-700 font-semibold text-xs shadow-xs transition"
            >
                Halaman Sebelumnya
            </button>
        </div>

        <div class="mt-8 pt-6 border-t border-line-100 text-[11px] text-ink-400">
            ASRI BOARDING HOUSE &bull; Tembalang, Semarang
        </div>
    </div>
</body>
</html>

