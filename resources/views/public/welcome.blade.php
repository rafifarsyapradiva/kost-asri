@extends('public.layouts.base')

@section('title', $siteSettings['seo']['meta_title'] ?? (($settings['kost_name'] ?? 'ASRI Boarding House') . ' — Tembalang, Semarang'))
@section('meta_description', $siteSettings['seo']['meta_description'] ?? ($settings['kost_tagline'] ?? 'Hunian kost asri 32 kamar di Tembalang, dekat kampus UNDIP Semarang.'))

@section('content')
<main class="min-h-screen">
    {{-- Hero Section --}}
    <section class="relative bg-gradient-to-b from-forest-900 to-forest-800 text-white py-20 px-6 sm:px-12">
        <div class="max-w-6xl mx-auto flex flex-col md:flex-row items-center justify-between gap-10">
            <div class="max-w-xl">
                <span class="inline-block px-3 py-1 bg-forest-700/60 text-cream-100 text-xs font-semibold rounded-full uppercase tracking-wider mb-4">
                    Tembalang, Semarang
                </span>
                <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight text-cream-50 leading-tight">
                    {{ $settings['kost_name'] ?? 'ASRI Boarding House' }}
                </h1>
                <p class="mt-4 text-base sm:text-lg text-cream-200 leading-relaxed">
                    {{ $settings['kost_tagline'] ?? 'Hunian kost asri 32 kamar di 2 lantai, nyaman, tenang, dan strategis dekat kampus UNDIP.' }}
                </p>
                <div class="mt-8 flex flex-wrap gap-4">
                    <a href="{{ route('public.home') }}#kamar" class="px-6 py-3 bg-terracotta-500 hover:bg-terracotta-600 text-white font-medium rounded-xl shadow-md transition-colors">
                        Lihat Tipe Kamar
                    </a>
                    <a href="/login" class="px-6 py-3 bg-white/10 hover:bg-white/20 text-white font-medium rounded-xl border border-white/20 transition-colors">
                        Masuk / Reservasi
                    </a>
                </div>
            </div>
            <div class="w-full md:w-80 rounded-2xl bg-white/10 backdrop-blur-md p-6 border border-white/15 text-cream-100 shadow-xl">
                <h2 class="text-lg font-bold text-white mb-2">Informasi Cepat</h2>
                <ul class="space-y-3 text-sm">
                    <li class="flex justify-between border-b border-white/10 pb-2">
                        <span>Total Kamar</span>
                        <span class="num font-semibold text-white">32 Kamar</span>
                    </li>
                    <li class="flex justify-between border-b border-white/10 pb-2">
                        <span>Jumlah Lantai</span>
                        <span class="num font-semibold text-white">2 Lantai</span>
                    </li>
                    <li class="flex justify-between border-b border-white/10 pb-2">
                        <span>WhatsApp</span>
                        <span class="num font-semibold text-white">{{ $settings['phone_number'] ?? '081234567890' }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </section>

    {{-- Room Types Section --}}
    @if($roomTypes->isNotEmpty())
    <section id="kamar" class="py-16 px-6 sm:px-12 max-w-6xl mx-auto">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <h2 class="text-3xl font-bold text-forest-900">Pilihan Tipe Kamar</h2>
            <p class="mt-2 text-ink-600">Standard, VIP, dan Deluxe dengan fasilitas lengkap dan sirkulasi udara optimal.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach($roomTypes as $type)
                <div class="rounded-2xl border border-line-200 bg-white overflow-hidden shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between">
                    <div>
                        @if($type->images->isNotEmpty())
                            <img src="{{ asset('storage/' . $type->images->first()->image_path) }}" alt="{{ $type->name }}" class="w-full h-48 object-cover">
                        @else
                            <div class="w-full h-48 bg-forest-100 flex items-center justify-center text-forest-700 font-semibold">
                                {{ $type->name }}
                            </div>
                        @endif
                        <div class="p-6">
                            <span class="text-xs uppercase font-bold tracking-wider text-forest-700">{{ strtoupper($type->room_class->value ?? 'STANDARD') }}</span>
                            <h3 class="text-xl font-bold text-forest-900 mt-1">{{ $type->name }}</h3>
                            <p class="mt-2 text-sm text-ink-600 line-clamp-2">{{ $type->description }}</p>
                            <div class="mt-4 pt-4 border-t border-line-100 flex items-baseline justify-between">
                                <span class="text-xs text-ink-500">Mulai dari</span>
                                <span class="num text-lg font-bold text-terracotta-600">
                                    Rp {{ number_format($type->monthly_rate, 0, ',', '.') }}<span class="text-xs font-normal text-ink-500">/bln</span>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="p-6 pt-0">
                        <a href="{{ route('public.room-type', $type->slug) }}" class="block w-full py-2.5 text-center bg-forest-800 hover:bg-forest-900 text-white font-medium rounded-xl transition-colors text-sm">
                            Detail Kamar
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Facilities Section --}}
    @if($facilities->isNotEmpty())
    <section class="py-16 px-6 sm:px-12 bg-cream-100/70">
        <div class="max-w-6xl mx-auto">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <h2 class="text-3xl font-bold text-forest-900">Fasilitas Bersama & Kamar</h2>
                <p class="mt-2 text-ink-600">Kenyamanan hunian dengan utilitas lengkap tanpa biaya tersembunyi.</p>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach($facilities as $facility)
                    <div class="bg-white p-5 rounded-2xl border border-line-200 shadow-sm flex items-start gap-4">
                        <div class="p-3 bg-forest-50 text-forest-700 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-forest-900 text-sm">{{ $facility->name }}</h3>
                            @if($facility->description)
                                <p class="text-xs text-ink-500 mt-1">{{ $facility->description }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Gallery Section --}}
    @if($galleries->isNotEmpty())
    <section class="py-16 px-6 sm:px-12 max-w-6xl mx-auto">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <h2 class="text-3xl font-bold text-forest-900">Galeri Foto</h2>
            <p class="mt-2 text-ink-600">Dokumentasi suasana kost, area komunal, kamar tidur, dan lingkungan asri.</p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($galleries as $gallery)
                <div class="rounded-xl overflow-hidden border border-line-200 aspect-square group relative">
                    <img src="{{ asset('storage/' . $gallery->image_path) }}" alt="{{ $gallery->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    <div class="absolute inset-0 bg-forest-950/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-4 text-white text-sm font-semibold">
                        {{ $gallery->title }}
                    </div>
                </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Videos Section --}}
    @if($videos->isNotEmpty())
    <section class="py-16 px-6 sm:px-12 bg-cream-100/70">
        <div class="max-w-6xl mx-auto">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <h2 class="text-3xl font-bold text-forest-900">Tur Video Kost</h2>
                <p class="mt-2 text-ink-600">Lihat suasana langsung hunian ASRI Boarding House.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                @foreach($videos as $video)
                    <div class="rounded-2xl overflow-hidden border border-line-200 bg-white shadow-sm">
                        <div class="aspect-video w-full">
                            <iframe class="w-full h-full" src="https://www.youtube-nocookie.com/embed/{{ $video->youtube_id }}" title="{{ $video->title }}" allowfullscreen></iframe>
                        </div>
                        <div class="p-4">
                            <h3 class="font-bold text-forest-900">{{ $video->title }}</h3>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Reservation Steps Section --}}
    @if($steps->isNotEmpty())
    <section class="py-16 px-6 sm:px-12 max-w-6xl mx-auto">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <h2 class="text-3xl font-bold text-forest-900">Alur Reservasi Mudah</h2>
            <p class="mt-2 text-ink-600">Langkah mudah menjadi bagian dari keluarga ASRI Boarding House.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($steps as $step)
                <div class="p-6 bg-white rounded-2xl border border-line-200 shadow-sm relative">
                    <span class="num absolute -top-4 -left-2 w-8 h-8 rounded-full bg-forest-800 text-white font-bold flex items-center justify-center text-sm shadow">
                        {{ $step->step_number }}
                    </span>
                    <h3 class="font-bold text-forest-900 mt-2">{{ $step->title }}</h3>
                    <p class="text-sm text-ink-600 mt-2">{{ $step->description }}</p>
                </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Testimonials Section --}}
    @if($testimonials->isNotEmpty())
    <section class="py-16 px-6 sm:px-12 bg-cream-100/70">
        <div class="max-w-6xl mx-auto">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <h2 class="text-3xl font-bold text-forest-900">Kata Penghuni Kost</h2>
                <p class="mt-2 text-ink-600">Pengalaman nyata tinggal di ASRI Boarding House.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($testimonials as $item)
                    <div class="p-6 bg-white rounded-2xl border border-line-200 shadow-sm flex flex-col justify-between">
                        <p class="text-sm text-ink-700 italic">"{{ $item->content }}"</p>
                        <div class="mt-6 pt-4 border-t border-line-100 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-forest-100 text-forest-800 font-bold flex items-center justify-center text-sm">
                                {{ strtoupper(substr($item->name, 0, 1)) }}
                            </div>
                            <div>
                                <h4 class="font-bold text-forest-900 text-sm">{{ $item->name }}</h4>
                                <p class="text-xs text-ink-500">{{ $item->origin_info }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Nearby Places Section --}}
    @if($nearbyPlaces->isNotEmpty())
    <section class="py-16 px-6 sm:px-12 max-w-6xl mx-auto">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <h2 class="text-3xl font-bold text-forest-900">Lokasi Strategis Sekitar</h2>
            <p class="mt-2 text-ink-600">Akses cepat ke kampus, rumah sakit, pusat belanja, dan kuliner Tembalang.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($nearbyPlaces as $place)
                <div class="p-5 bg-white rounded-2xl border border-line-200 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs uppercase font-semibold text-forest-700">{{ $place->category }}</span>
                        <h3 class="font-bold text-forest-900 text-base mt-0.5">{{ $place->name }}</h3>
                    </div>
                    <div class="text-right">
                        <span class="num text-sm font-bold text-terracotta-600">{{ $place->distance_text }}</span>
                        @if($place->travel_minutes)
                            <p class="num text-xs text-ink-500">~{{ $place->travel_minutes }} menit</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- FAQs Section --}}
    @if($faqs->isNotEmpty())
    <section class="py-16 px-6 sm:px-12 bg-cream-100/70">
        <div class="max-w-4xl mx-auto">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <h2 class="text-3xl font-bold text-forest-900">Pertanyaan yang Sering Diajukan</h2>
                <p class="mt-2 text-ink-600">Informasi seputar peraturan sewa, pembayaran, dan fasilitas kost.</p>
            </div>
            <div class="space-y-4" x-data="{ openFaq: null }">
                @foreach($faqs as $index => $faq)
                    <div class="rounded-2xl border border-line-200 bg-white overflow-hidden shadow-sm">
                        <button
                            type="button"
                            @click="openFaq = openFaq === {{ $index }} ? null : {{ $index }}"
                            class="w-full text-left p-5 font-bold text-forest-900 flex justify-between items-center"
                        >
                            <span>{{ $faq->question }}</span>
                            <span x-text="openFaq === {{ $index }} ? '−' : '+'" class="text-lg text-forest-700"></span>
                        </button>
                        <div x-show="openFaq === {{ $index }}" x-cloak class="px-5 pb-5 pt-0 text-sm text-ink-700 border-t border-line-100">
                            {!! $faq->answer !!}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif
    {{-- About Us Section (FR-CMS-08) --}}
    @if(!empty($siteSettings['about_us']) && (!empty($siteSettings['about_us']['about_title']) || !empty($siteSettings['about_us']['about_body'])))
    <section class="py-16 px-6 sm:px-12 max-w-6xl mx-auto">
        <div class="flex flex-col md:flex-row items-center gap-10">
            @if(!empty($siteSettings['about_us']['about_image']))
                <div class="w-full md:w-1/2 rounded-2xl overflow-hidden shadow-md">
                    <img src="{{ asset('storage/' . $siteSettings['about_us']['about_image']) }}" alt="{{ $siteSettings['about_us']['about_title'] ?? 'Tentang ASRI' }}" class="w-full h-80 object-cover">
                </div>
            @endif
            <div class="w-full {{ !empty($siteSettings['about_us']['about_image']) ? 'md:w-1/2' : '' }}">
                <span class="text-xs uppercase font-bold tracking-wider text-forest-700">Tentang Hunian</span>
                <h2 class="text-3xl font-bold text-forest-900 mt-2">{{ $siteSettings['about_us']['about_title'] ?? 'Tentang ASRI Boarding House' }}</h2>
                <div class="mt-4 text-ink-700 leading-relaxed text-sm prose">
                    {!! nl2br(e($siteSettings['about_us']['about_body'] ?? '')) !!}
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- Location & Contact Section (FR-CMS-09) --}}
    @if(!empty($siteSettings['location']) && (!empty($siteSettings['location']['address']) || !empty($siteSettings['location']['google_maps_embed'])))
    <section class="py-16 px-6 sm:px-12 bg-cream-100/70">
        <div class="max-w-6xl mx-auto">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <h2 class="text-3xl font-bold text-forest-900">Lokasi & Kontak</h2>
                <p class="mt-2 text-ink-600">Akses mudah di kawasan Tembalang, dekat dengan pusat aktivitas Anda.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="bg-white p-6 rounded-2xl border border-line-200 shadow-sm flex flex-col justify-between">
                    <div>
                        <h3 class="font-bold text-forest-900 text-lg mb-4">Informasi Alamat & Operasional</h3>
                        <div class="space-y-4 text-sm text-ink-700">
                            <div>
                                <span class="font-semibold block text-forest-800">Alamat:</span>
                                <span>{{ $siteSettings['location']['address'] ?? 'Tembalang, Semarang' }}</span>
                            </div>
                            @if(!empty($siteSettings['location']['operating_hours']))
                                <div>
                                    <span class="font-semibold block text-forest-800">Jam Operasional:</span>
                                    <span>{{ $siteSettings['location']['operating_hours'] }}</span>
                                </div>
                            @endif
                            @if(!empty($siteSettings['location']['phone']))
                                <div>
                                    <span class="font-semibold block text-forest-800">Telepon / WhatsApp:</span>
                                    <span class="num">{{ $siteSettings['location']['phone'] }}</span>
                                </div>
                            @endif
                            @if(!empty($siteSettings['location']['email']))
                                <div>
                                    <span class="font-semibold block text-forest-800">Email:</span>
                                    <span>{{ $siteSettings['location']['email'] }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="rounded-2xl overflow-hidden border border-line-200 bg-white shadow-sm min-h-[280px]">
                    @if(!empty($siteSettings['location']['google_maps_embed']))
                        <div class="w-full h-full min-h-[280px]">
                            {!! $siteSettings['location']['google_maps_embed'] !!}
                        </div>
                    @else
                        <div class="w-full h-full flex items-center justify-center bg-forest-50 text-forest-700 text-sm font-medium p-6">
                            Peta Google Maps
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
    @endif
</main>
@endsection

