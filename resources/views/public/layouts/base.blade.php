<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="@yield('meta_description', 'ASRI Boarding House — Hunian kost asri 32 kamar 2 lantai di Tembalang, Semarang. Nyaman, tenang, dekat kampus UNDIP.')">
        <meta name="author" content="ASRI Boarding House">

        <!-- Open Graph / SEO (FE-014) -->
        <meta property="og:title" content="@yield('title', 'ASRI Boarding House — Tembalang, Semarang')">
        <meta property="og:description" content="@yield('meta_description', 'Hunian kost asri 32 kamar di Tembalang, dekat kampus UNDIP Semarang.')">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="@yield('og_image', asset('images/asri-cover.jpg'))">

        <title>@yield('title', 'ASRI Boarding House — Tembalang, Semarang')</title>

        @vite(['resources/css/public.css', 'resources/js/public.js'])
    </head>
    <body class="min-h-screen bg-cream-50 font-sans text-ink-900 antialiased flex flex-col justify-between" x-data="{ mobileMenuOpen: false }">
        <!-- Sticky Navbar (FE-008) -->
        <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-line-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                <!-- Brand -->
                <a href="{{ route('public.home') }}" class="flex items-center gap-3 group">
                    <div class="h-10 w-10 rounded-2xl bg-forest-900 text-white flex items-center justify-center font-black text-xl shadow-md group-hover:bg-forest-700 transition">
                        A
                    </div>
                    <div>
                        <span class="font-extrabold tracking-tight text-forest-900 text-lg block leading-none">
                            ASRI BOARDING HOUSE
                        </span>
                        <span class="text-[11px] text-terracotta-600 font-semibold tracking-wider">
                            Tembalang, Semarang
                        </span>
                    </div>
                </a>

                <!-- Desktop Navigation Links -->
                <nav class="hidden lg:flex items-center gap-6 text-sm font-semibold text-ink-700">
                    <a href="{{ route('public.home') }}#tentang" class="hover:text-forest-900 transition">Tentang</a>
                    <a href="{{ route('public.home') }}#kamar" class="hover:text-forest-900 transition">Tipe Kamar</a>
                    <a href="{{ route('public.home') }}#fasilitas" class="hover:text-forest-900 transition">Fasilitas</a>
                    <a href="{{ route('public.home') }}#galeri" class="hover:text-forest-900 transition">Galeri</a>
                    <a href="{{ route('public.home') }}#alur" class="hover:text-forest-900 transition">Alur Sewa</a>
                    <a href="{{ route('public.home') }}#testimoni" class="hover:text-forest-900 transition">Testimoni</a>
                    <a href="{{ route('public.home') }}#faq" class="hover:text-forest-900 transition">FAQ</a>
                    <a href="{{ route('public.home') }}#lokasi" class="hover:text-forest-900 transition">Lokasi</a>
                </nav>

                <!-- Action Buttons -->
                <div class="hidden sm:flex items-center gap-3">
                    <a
                        href="/login"
                        class="px-4 py-2 text-sm font-semibold text-forest-900 hover:text-forest-700 transition"
                    >
                        Masuk
                    </a>
                    <a
                        href="{{ route('reservation.step1') }}"
                        class="px-5 py-2.5 rounded-xl bg-terracotta-500 hover:bg-terracotta-400 text-ink-900 font-bold text-sm shadow-sm transition"
                    >
                        Reservasi Online
                    </a>
                </div>

                <!-- Mobile Menu Button -->
                <button
                    type="button"
                    class="lg:hidden p-2 text-ink-700 hover:text-forest-900"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    aria-label="Buka Menu"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>

            <!-- Mobile Drawer Menu -->
            <div
                x-show="mobileMenuOpen"
                x-cloak
                x-collapse
                class="lg:hidden border-t border-line-200 bg-white px-4 pt-3 pb-6 space-y-3"
            >
                <a href="{{ route('public.home') }}#tentang" @click="mobileMenuOpen = false" class="block py-2 text-sm font-semibold text-ink-700">Tentang</a>
                <a href="{{ route('public.home') }}#kamar" @click="mobileMenuOpen = false" class="block py-2 text-sm font-semibold text-ink-700">Tipe Kamar</a>
                <a href="{{ route('public.home') }}#fasilitas" @click="mobileMenuOpen = false" class="block py-2 text-sm font-semibold text-ink-700">Fasilitas</a>
                <a href="{{ route('public.home') }}#galeri" @click="mobileMenuOpen = false" class="block py-2 text-sm font-semibold text-ink-700">Galeri</a>
                <a href="{{ route('public.home') }}#alur" @click="mobileMenuOpen = false" class="block py-2 text-sm font-semibold text-ink-700">Alur Sewa</a>
                <a href="{{ route('public.home') }}#testimoni" @click="mobileMenuOpen = false" class="block py-2 text-sm font-semibold text-ink-700">Testimoni</a>
                <a href="{{ route('public.home') }}#faq" @click="mobileMenuOpen = false" class="block py-2 text-sm font-semibold text-ink-700">FAQ</a>
                <a href="{{ route('public.home') }}#lokasi" @click="mobileMenuOpen = false" class="block py-2 text-sm font-semibold text-ink-700">Lokasi</a>
                <div class="pt-4 border-t border-line-100 flex flex-col gap-2">
                    <a href="/login" class="text-center py-2.5 rounded-xl border border-line-200 text-sm font-semibold text-forest-900">
                        Masuk Akun
                    </a>
                    <a href="{{ route('reservation.step1') }}" class="text-center py-2.5 rounded-xl bg-terracotta-500 text-ink-900 font-bold text-sm shadow">
                        Reservasi Online
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <div class="flex-1">
            @yield('content')
        </div>

        <!-- Floating WhatsApp CTA (FE-008) -->
        <aside class="fixed bottom-6 right-6 z-40">
            <a
                href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $settings['whatsapp_number'] ?? ($settings['phone_number'] ?? '6281234567890')) }}?text=Halo%20Admin%20Kost%20ASRI,%20saya%20tertarik%20dengan%20kamar%20kost."
                target="_blank"
                rel="noopener noreferrer"
                class="group flex items-center gap-3 rounded-full bg-[#25D366] px-5 py-3.5 text-white font-bold text-sm shadow-xl hover:bg-[#20ba5a] transition-all transform hover:scale-105"
                aria-label="Hubungi WhatsApp Kost"
            >
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-white"></span>
                </span>
                <!-- WhatsApp SVG Icon -->
                <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24">
                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                </svg>
                <span class="hidden md:inline">Tanya Kami di WhatsApp</span>
                <span class="md:hidden">WhatsApp</span>
            </a>
        </aside>

        <!-- Footer -->
        <footer class="bg-forest-900 text-white pt-16 pb-8 border-t border-forest-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-10 pb-12 border-b border-forest-800/80">
                    <div class="md:col-span-2">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="h-10 w-10 rounded-2xl bg-terracotta-500 text-ink-900 flex items-center justify-center font-black text-xl shadow">
                                A
                            </div>
                            <span class="font-bold text-xl tracking-tight text-white">ASRI BOARDING HOUSE</span>
                        </div>
                        <p class="text-sage-100/80 text-sm max-w-md leading-relaxed">
                            Hunian kost asri 32 kamar 2 lantai di Tembalang, Semarang. Mengutamakan kenyamanan, ketenangan belajar, kebersihan, dan transparansi aturan bagi mahasiswa dan profesional muda.
                        </p>
                        <div class="mt-6 flex flex-wrap gap-4 text-xs font-semibold text-sage-300">
                            <span>✓ Bebas Jam Malam Sopan</span>
                            <span>✓ WiFi Berkecepatan Tinggi</span>
                            <span>✓ CCTV & Akses Kartu Aman</span>
                        </div>
                    </div>

                    <div>
                        <h4 class="font-bold text-white text-sm uppercase tracking-wider mb-4">Navigasi Cepat</h4>
                        <ul class="space-y-2.5 text-sm text-sage-100/80">
                            <li><a href="{{ route('public.home') }}#tentang" class="hover:text-white transition">Tentang Kami</a></li>
                            <li><a href="{{ route('public.home') }}#kamar" class="hover:text-white transition">Tipe & Tarif Kamar</a></li>
                            <li><a href="{{ route('public.home') }}#fasilitas" class="hover:text-white transition">Fasilitas Kamar & Umum</a></li>
                            <li><a href="{{ route('public.home') }}#alur" class="hover:text-white transition">Alur 5 Langkah Reservasi</a></li>
                            <li><a href="{{ route('public.home') }}#faq" class="hover:text-white transition">Tanya Jawab (FAQ)</a></li>
                            <li><a href="{{ route('public.home') }}#lokasi" class="hover:text-white transition">Peta & Lokasi Kampus</a></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="font-bold text-white text-sm uppercase tracking-wider mb-4">Kontak & Lokasi</h4>
                        <ul class="space-y-3 text-sm text-sage-100/80">
                            <li class="flex items-start gap-2">
                                <span class="text-terracotta-400">📍</span>
                                <span>Tembalang, Semarang, Jawa Tengah (Dekat Kampus UNDIP & Polines)</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-terracotta-400">📞</span>
                                <span class="num">{{ $settings['phone_number'] ?? '081234567890' }}</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-terracotta-400">✉️</span>
                                <span>info@kost-asri.com</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-terracotta-400">⏰</span>
                                <span>07:00 - 21:00 WIB</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="pt-8 flex flex-col sm:flex-row items-center justify-between text-xs text-sage-300 gap-4">
                    <p>&copy; {{ date('Y') }} ASRI BOARDING HOUSE. Seluruh hak cipta dilindungi.</p>
                    <p>Mini ERP & Booking Platform didukung oleh Laravel 12 & Alpine.js.</p>
                </div>
            </div>
        </footer>
    </body>
</html>
