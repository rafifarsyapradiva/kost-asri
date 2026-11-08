@extends('public.layouts.base')

@section('title', $siteSettings['seo']['meta_title'] ?? (($settings['kost_name'] ?? 'ASRI Boarding House') . ' — Hunian Kost Tembalang, Semarang'))
@section('meta_description', $siteSettings['seo']['meta_description'] ?? ($settings['kost_tagline'] ?? 'Hunian kost asri 32 kamar 2 lantai di Tembalang, Semarang dekat kampus UNDIP.'))

@section('content')
<main class="min-h-screen">
    {{-- Section 1: Hero Biophilic & Quick Check Widget (FE-009) --}}
    <section class="relative bg-gradient-to-b from-forest-900 via-forest-800 to-forest-900 text-white pt-16 pb-24 px-4 sm:px-6 lg:px-8 overflow-hidden">
        <!-- Background subtle pattern -->
        <div class="absolute inset-0 opacity-5 pointer-events-none bg-[radial-gradient(#95D5B2_1px,transparent_1px)] [background-size:16px_16px]"></div>

        <div class="max-w-7xl mx-auto relative z-10 flex flex-col lg:flex-row items-center justify-between gap-12">
            <!-- Left Hero Content -->
            <div class="max-w-2xl text-center lg:text-left">
                <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-forest-700/60 border border-forest-600/50 text-sage-100 text-xs font-semibold tracking-wide uppercase mb-6">
                    <span class="h-2 w-2 rounded-full bg-terracotta-400"></span>
                    Tembalang, Semarang • Dekat Kampus UNDIP
                </span>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight text-white leading-tight">
                    Hunian Kost Asri, Tenang & Modern di Tembalang
                </h1>
                <p class="mt-6 text-base sm:text-lg text-sage-100/90 leading-relaxed max-w-xl mx-auto lg:mx-0">
                    {{ $settings['kost_tagline'] ?? '32 kamar eksklusif di 2 lantai berarsitektur biophilic yang ramah sirkulasi udara, bebas jam malam sopan, dan transparan dalam sistem sewa.' }}
                </p>

                <!-- Counter stats with .num -->
                <div class="mt-8 grid grid-cols-3 gap-4 border-t border-forest-700/60 pt-6 max-w-md mx-auto lg:mx-0 text-center lg:text-left">
                    <div>
                        <span class="num text-2xl sm:text-3xl font-extrabold text-terracotta-400 block">32</span>
                        <span class="text-xs text-sage-200 font-medium">Total Kamar</span>
                    </div>
                    <div>
                        <span class="num text-2xl sm:text-3xl font-extrabold text-terracotta-400 block">2</span>
                        <span class="text-xs text-sage-200 font-medium">Lantai Hunian</span>
                    </div>
                    <div>
                        <span class="num text-2xl sm:text-3xl font-extrabold text-terracotta-400 block">3</span>
                        <span class="text-xs text-sage-200 font-medium">Pilihan Kelas</span>
                    </div>
                </div>
            </div>

            <!-- Right: Quick Check Widget Card (FE-009) -->
            <div class="w-full lg:w-96 rounded-3xl bg-white p-7 text-ink-900 shadow-2xl border border-white/20" x-data="{ checkInDate: '', rentType: 'monthly' }">
                <div class="mb-4">
                    <h2 class="text-lg font-bold text-forest-900">Cek Ketersediaan Kamar</h2>
                    <p class="text-xs text-ink-500 mt-0.5">Pilih tanggal dan cari kamar idaman Anda</p>
                </div>

                <form action="{{ route('reservation.step1') }}" method="GET" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-ink-700 mb-1">
                            Rencana Tanggal Masuk
                        </label>
                        <input
                            type="date"
                            name="check_in"
                            x-model="checkInDate"
                            min="{{ date('Y-m-d') }}"
                            required
                            class="w-full rounded-xl border border-line-200 bg-slate-50 px-3.5 py-2.5 text-sm text-ink-900 focus:border-forest-500 focus:bg-white focus:outline-none"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-ink-700 mb-1">
                            Pilihan Siklus Sewa
                        </label>
                        <select
                            name="rent_type"
                            x-model="rentType"
                            class="w-full rounded-xl border border-line-200 bg-slate-50 px-3.5 py-2.5 text-sm text-ink-900 focus:border-forest-500 focus:bg-white focus:outline-none"
                        >
                            <option value="monthly">Sewa Bulanan (Paling Populer)</option>
                            <option value="weekly">Sewa Mingguan (Rolling 7 Hari)</option>
                            <option value="daily">Sewa Harian</option>
                        </select>
                    </div>

                    <button
                        type="submit"
                        class="w-full py-3 rounded-xl bg-terracotta-500 hover:bg-terracotta-400 text-ink-900 font-bold text-sm shadow transition flex items-center justify-center gap-2"
                    >
                        <span>Cari & Pilih Kamar</span>
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                </form>

                <div class="mt-4 pt-4 border-t border-line-100 flex items-center justify-between text-[11px] text-ink-500">
                    <span>⚡ Konfirmasi Instan</span>
                    <span>🔒 Pembayaran Aman Midtrans</span>
                </div>
            </div>
        </div>
    </section>

    {{-- Section 2: Tentang Kami (Biophilic & Profil) (FE-010) --}}
    <section id="tentang" class="py-20 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div class="relative rounded-3xl overflow-hidden shadow-xl border border-line-200 aspect-[4/3]">
                @if(!empty($siteSettings['about_us']['about_image']))
                    <img
                        src="{{ asset('storage/' . $siteSettings['about_us']['about_image']) }}"
                        alt="Tentang ASRI Boarding House"
                        class="w-full h-full object-cover"
                        loading="lazy"
                    />
                @else
                    <div class="w-full h-full bg-gradient-to-tr from-forest-900 to-forest-700 flex flex-col items-center justify-center text-white p-8 text-center">
                        <span class="text-5xl font-black mb-2 text-terracotta-400">ASRI</span>
                        <p class="text-sage-100 text-sm max-w-sm">Harmoni kenyamanan dan ketenangan di jantung Tembalang, Semarang.</p>
                    </div>
                @endif
            </div>

            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-forest-700">Hunian Pilihan Mahasiswa & Profesional</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-forest-900 mt-2 tracking-tight">
                    {{ $siteSettings['about_us']['about_title'] ?? 'Kenyamanan Serasa di Rumah Sendiri' }}
                </h2>
                <div class="mt-5 text-sm sm:text-base text-ink-700 leading-relaxed space-y-4">
                    @if(!empty($siteSettings['about_us']['about_body']))
                        {!! nl2br(e($siteSettings['about_us']['about_body'])) !!}
                    @else
                        <p>
                            ASRI Boarding House dirancang khusus untuk menciptakan lingkungan istirahat dan belajar yang kondusif. Berlokasi strategis di kawasan Tembalang, Anda dapat menjangkau kampus Universitas Diponegoro (UNDIP) dan Politeknik Negeri Semarang hanya dalam hitungan menit.
                        </p>
                        <p>
                            Dengan kapasitas terukur 32 kamar yang tersebar di Lantai 1 dan Lantai 2, suasana hunian tetap tenang, tidak bising, dan memiliki sirkulasi udara serta pencahayaan alami yang optimal.
                        </p>
                    @endif
                </div>

                <div class="mt-8 flex flex-wrap gap-4">
                    <a href="#kamar" class="px-5 py-2.5 rounded-xl bg-forest-900 hover:bg-forest-800 text-white font-semibold text-sm transition">
                        Jelajahi Tipe Kamar
                    </a>
                    <a href="#lokasi" class="px-5 py-2.5 rounded-xl border border-line-200 text-ink-700 hover:bg-slate-50 font-semibold text-sm transition">
                        Lihat Peta Lokasi
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Section 3: Pilihan Tipe Kamar (FE-011) --}}
    <section id="kamar" class="py-20 px-4 sm:px-6 lg:px-8 bg-cream-100/60 border-y border-line-200">
        <div class="max-w-7xl mx-auto">
            <div class="text-center max-w-3xl mx-auto mb-14">
                <span class="text-xs font-bold uppercase tracking-wider text-forest-700">Pilihan Ruang Istirahat</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-forest-900 mt-1">Tipe & Tarif Kamar</h2>
                <p class="mt-3 text-sm sm:text-base text-ink-600">
                    Setiap kamar dirawat prima dengan fasilitas penunjang belajar, kamar mandi bersih, dan perabot kokoh.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach($roomTypes as $type)
                    @php
                        $rc = strtolower($type->room_class->value ?? 'standard');
                        $borderAccent = $rc === 'vip' ? 'border-l-[#2563EB]' : ($rc === 'deluxe' ? 'border-l-[#C9A227]' : 'border-l-[#6B7280]');
                    @endphp
                    <div class="rounded-3xl border border-line-200 bg-white overflow-hidden shadow-sm hover:shadow-md transition-all flex flex-col justify-between border-l-4 {{ $borderAccent }}">
                        <div>
                            <!-- Room Image -->
                            <div class="relative h-56 bg-slate-100 overflow-hidden">
                                @if($type->images->isNotEmpty())
                                    <img
                                        src="{{ asset('storage/' . $type->images->first()->image_path) }}"
                                        alt="{{ $type->name }}"
                                        class="w-full h-full object-cover transition-transform duration-300 hover:scale-105"
                                        loading="lazy"
                                    />
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-forest-50 text-forest-800 font-bold text-lg">
                                        {{ $type->name }}
                                    </div>
                                @endif
                                <span class="num absolute top-3 right-3 rounded-full bg-forest-900/80 backdrop-blur-xs px-3 py-1 text-xs font-bold text-white shadow">
                                    {{ $type->size_m2 }} m²
                                </span>
                            </div>

                            <div class="p-6">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-extrabold uppercase tracking-wider text-forest-800">
                                        {{ strtoupper($type->room_class->value ?? 'STANDARD') }}
                                    </span>
                                </div>

                                <h3 class="text-xl font-bold text-forest-900">{{ $type->name }}</h3>
                                <p class="mt-2 text-xs text-ink-600 line-clamp-3 leading-relaxed">
                                    {{ $type->description }}
                                </p>

                                <!-- Price breakdown -->
                                <div class="mt-6 pt-4 border-t border-line-100 space-y-1.5 text-xs text-ink-600">
                                    <div class="flex justify-between items-baseline">
                                        <span>Bulanan</span>
                                        <span class="num text-base font-bold text-terracotta-600">
                                            Rp {{ number_format($type->monthly_rate, 0, ',', '.') }}<span class="text-[10px] font-normal text-ink-500">/bln</span>
                                        </span>
                                    </div>
                                    @if($type->weekly_rate)
                                    <div class="flex justify-between items-baseline">
                                        <span>Mingguan</span>
                                        <span class="num font-semibold text-ink-900">
                                            Rp {{ number_format($type->weekly_rate, 0, ',', '.') }}<span class="text-[10px] font-normal text-ink-500">/mgg</span>
                                        </span>
                                    </div>
                                    @endif
                                    @if($type->daily_rate)
                                    <div class="flex justify-between items-baseline">
                                        <span>Harian</span>
                                        <span class="num font-semibold text-ink-900">
                                            Rp {{ number_format($type->daily_rate, 0, ',', '.') }}<span class="text-[10px] font-normal text-ink-500">/mlm</span>
                                        </span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="p-6 pt-0 flex gap-2">
                            <a
                                href="{{ route('public.room-type', $type->slug) }}"
                                class="flex-1 py-2.5 text-center rounded-xl border border-line-200 text-xs font-semibold text-forest-900 hover:bg-slate-50 transition"
                            >
                                Detail Fasilitas
                            </a>
                            <a
                                href="{{ route('reservation.step1') }}"
                                class="py-2.5 px-4 rounded-xl bg-terracotta-500 hover:bg-terracotta-400 text-ink-900 font-bold text-xs shadow-xs transition"
                            >
                                Pesan
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Section 4: Fasilitas Lengkap (FE-010) --}}
    @if($facilities->isNotEmpty())
    <section id="fasilitas" class="py-20 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto" x-data="{ tab: 'all' }">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="text-xs font-bold uppercase tracking-wider text-forest-700">Kenyamanan Maksimal</span>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-forest-900 mt-1">Fasilitas Kost</h2>
            <p class="mt-2 text-sm text-ink-600">Segala kebutuhan dasar dan pendukung belajar telah disiapkan untuk Anda.</p>

            <!-- Tabs -->
            <div class="mt-6 inline-flex rounded-xl border border-line-200 bg-slate-50 p-1">
                <button
                    type="button"
                    @click="tab = 'all'"
                    :class="tab === 'all' ? 'bg-white text-forest-900 shadow-xs' : 'text-ink-600 hover:text-ink-900'"
                    class="rounded-lg px-4 py-2 text-xs font-semibold transition"
                >
                    Semua Fasilitas
                </button>
                <button
                    type="button"
                    @click="tab = 'room'"
                    :class="tab === 'room' ? 'bg-white text-forest-900 shadow-xs' : 'text-ink-600 hover:text-ink-900'"
                    class="rounded-lg px-4 py-2 text-xs font-semibold transition"
                >
                    Fasilitas Kamar
                </button>
                <button
                    type="button"
                    @click="tab = 'public'"
                    :class="tab === 'public' ? 'bg-white text-forest-900 shadow-xs' : 'text-ink-600 hover:text-ink-900'"
                    class="rounded-lg px-4 py-2 text-xs font-semibold transition"
                >
                    Fasilitas Bersama
                </button>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
            @foreach($facilities as $facility)
                <div
                    x-show="tab === 'all' || (tab === 'room' && '{{ $facility->category }}' === 'room') || (tab === 'public' && '{{ $facility->category }}' !== 'room')"
                    class="p-5 rounded-2xl border border-line-200 bg-white shadow-xs flex items-start gap-4 transition hover:shadow-sm"
                >
                    <div class="p-2.5 rounded-xl bg-forest-50 text-forest-700 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-forest-900 text-sm">{{ $facility->name }}</h4>
                        @if($facility->description)
                            <p class="text-xs text-ink-500 mt-1 leading-snug">{{ $facility->description }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Section 5: Galeri Foto (FE-012) --}}
    @if($galleries->isNotEmpty())
    <section id="galeri" class="py-20 px-4 sm:px-6 lg:px-8 bg-cream-100/60 border-t border-line-200" x-data="{ lightboxOpen: false, activeImg: '' }">
        <div class="max-w-7xl mx-auto">
            <div class="text-center max-w-3xl mx-auto mb-12">
                <span class="text-xs font-bold uppercase tracking-wider text-forest-700">Dokumentasi Nyata</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-forest-900 mt-1">Suasana Lingkungan Kost</h2>
                <p class="mt-2 text-sm text-ink-600">Lihat sudut-sudut kamar, dapur bersama, lorong sejuk, dan area parkir ASRI.</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($galleries as $gallery)
                    <div
                        @click="activeImg = '{{ asset('storage/' . $gallery->image_path) }}'; lightboxOpen = true"
                        class="rounded-2xl overflow-hidden border border-line-200 aspect-square group relative cursor-pointer bg-slate-100 shadow-xs"
                    >
                        <img
                            src="{{ asset('storage/' . $gallery->image_path) }}"
                            alt="{{ $gallery->title }}"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            loading="lazy"
                        />
                        <div class="absolute inset-0 bg-forest-950/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-4 text-white text-xs font-bold">
                            {{ $gallery->title }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Lightbox Modal -->
        <div
            x-show="lightboxOpen"
            x-cloak
            @keydown.escape.window="lightboxOpen = false"
            class="fixed inset-0 z-50 bg-black/90 backdrop-blur-xs flex items-center justify-center p-4"
        >
            <button
                type="button"
                @click="lightboxOpen = false"
                class="absolute top-6 right-6 text-white text-3xl font-bold p-2 hover:text-sage-300"
            >
                &times;
            </button>
            <img :src="activeImg" alt="Foto Galeri" class="max-w-full max-h-[85vh] rounded-2xl shadow-2xl object-contain">
        </div>
    </section>
    @endif

    {{-- Section 6: Video YouTube (FE-012) --}}
    @if($videos->isNotEmpty())
    <section class="py-20 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto" x-data="{ videoModalOpen: false, activeYoutubeId: '' }">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="text-xs font-bold uppercase tracking-wider text-forest-700">Room Tour Virtual</span>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-forest-900 mt-1">Video Tur Hunian</h2>
            <p class="mt-2 text-sm text-ink-600">Saksikan video penjelajahan langsung fasilitas dan kamar ASRI Boarding House.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            @foreach($videos as $video)
                <div class="rounded-3xl border border-line-200 bg-white overflow-hidden shadow-sm hover:shadow-md transition">
                    <div
                        @click="activeYoutubeId = '{{ $video->youtube_id }}'; videoModalOpen = true"
                        class="relative aspect-video bg-black cursor-pointer group flex items-center justify-center"
                    >
                        <img
                            src="https://img.youtube.com/vi/{{ $video->youtube_id }}/hqdefault.jpg"
                            alt="{{ $video->title }}"
                            class="w-full h-full object-cover group-hover:opacity-80 transition"
                        />
                        <div class="absolute h-14 w-14 rounded-full bg-terracotta-500 text-white flex items-center justify-center shadow-lg group-hover:scale-110 transition">
                            <svg class="h-6 w-6 ml-0.5 fill-current" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="p-5">
                        <h4 class="font-bold text-forest-900 text-base">{{ $video->title }}</h4>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Video Modal -->
        <div
            x-show="videoModalOpen"
            x-cloak
            @keydown.escape.window="videoModalOpen = false; activeYoutubeId = ''"
            class="fixed inset-0 z-50 bg-black/90 backdrop-blur-xs flex items-center justify-center p-4"
        >
            <div class="relative w-full max-w-4xl aspect-video rounded-2xl overflow-hidden shadow-2xl bg-black">
                <button
                    type="button"
                    @click="videoModalOpen = false; activeYoutubeId = ''"
                    class="absolute top-4 right-4 z-10 text-white text-2xl font-bold p-2 bg-black/50 rounded-full hover:bg-black"
                >
                    &times;
                </button>
                <template x-if="videoModalOpen && activeYoutubeId">
                    <iframe
                        class="w-full h-full"
                        :src="'https://www.youtube-nocookie.com/embed/' + activeYoutubeId + '?autoplay=1'"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen
                    ></iframe>
                </template>
            </div>
        </div>
    </section>
    @endif

    {{-- Section 7: Alur 5 Langkah Reservasi (FE-013) --}}
    @if($steps->isNotEmpty())
    <section id="alur" class="py-20 px-4 sm:px-6 lg:px-8 bg-forest-900 text-white">
        <div class="max-w-7xl mx-auto">
            <div class="text-center max-w-3xl mx-auto mb-14">
                <span class="text-xs font-bold uppercase tracking-wider text-sage-300">Transparan & Mudah</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-white mt-1">Alur 5 Langkah Menjadi Penghuni</h2>
                <p class="mt-2 text-sm text-sage-200">Seluruh proses reservasi dilakukan online secara instan dan tanpa ribet.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
                @foreach($steps as $step)
                    <div class="p-6 rounded-2xl border border-forest-800 bg-forest-800/60 backdrop-blur-xs relative flex flex-col justify-between">
                        <div>
                            <span class="num inline-flex h-9 w-9 items-center justify-center rounded-xl bg-terracotta-500 text-ink-900 font-extrabold text-sm mb-4 shadow">
                                {{ $step->step_number }}
                            </span>
                            <h4 class="font-bold text-white text-base">{{ $step->title }}</h4>
                            <p class="mt-2 text-xs text-sage-100/80 leading-relaxed">{{ $step->description }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Section 8: Testimoni Penghuni (Slider Auto-Play) (FE-013) --}}
    @if($testimonials->isNotEmpty())
    <section id="testimoni" class="py-20 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto" x-data="{ currentTestimonial: 0, total: {{ $testimonials->count() }}, timer: null }" x-init="timer = setInterval(() => { currentTestimonial = (currentTestimonial + 1) % total }, 4500)" @mouseenter="clearInterval(timer)" @mouseleave="timer = setInterval(() => { currentTestimonial = (currentTestimonial + 1) % total }, 4500)">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="text-xs font-bold uppercase tracking-wider text-forest-700">Kesan Nyata</span>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-forest-900 mt-1">Kata Mereka Tentang ASRI</h2>
            <p class="mt-2 text-sm text-ink-600">Pengalaman jujur dari mahasiswa dan penghuni yang tinggal bersama kami.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($testimonials as $idx => $item)
                <div class="p-7 rounded-3xl border border-line-200 bg-white shadow-xs flex flex-col justify-between hover:shadow-md transition">
                    <div>
                        <!-- 5 Star Rating -->
                        <div class="flex text-amber-400 mb-3 text-sm">
                            ★★★★★
                        </div>
                        <p class="text-sm text-ink-700 italic leading-relaxed">
                            "{{ $item->content }}"
                        </p>
                    </div>

                    <div class="mt-6 pt-4 border-t border-line-100 flex items-center gap-3">
                        <div class="h-10 w-10 rounded-full bg-forest-100 text-forest-800 font-bold flex items-center justify-center text-sm">
                            {{ strtoupper(substr($item->name, 0, 1)) }}
                        </div>
                        <div>
                            <h4 class="font-bold text-forest-900 text-sm">{{ $item->name }}</h4>
                            <p class="text-xs text-ink-500">{{ $item->origin_info ?? 'Penghuni Kost' }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Section: FAQ Accordion (FE-013) --}}
    @if($faqs->isNotEmpty())
    <section id="faq" class="py-20 px-4 sm:px-6 lg:px-8 bg-cream-100/60 border-t border-line-200">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-12">
                <span class="text-xs font-bold uppercase tracking-wider text-forest-700">Bantuan Informasi</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-forest-900 mt-1">Pertanyaan yang Sering Diajukan</h2>
                <p class="mt-2 text-sm text-ink-600">Kumpulan jawaban atas pertanyaan umum calon penghuni.</p>
            </div>

            <div class="space-y-4" x-data="{ openFaq: null }">
                @foreach($faqs as $index => $faq)
                    <div class="rounded-2xl border border-line-200 bg-white overflow-hidden shadow-xs">
                        <button
                            type="button"
                            @click="openFaq = openFaq === {{ $index }} ? null : {{ $index }}"
                            class="w-full text-left p-5 font-bold text-forest-900 flex justify-between items-center transition hover:bg-slate-50"
                        >
                            <span class="text-sm sm:text-base pr-4">{{ $faq->question }}</span>
                            <span
                                class="h-7 w-7 rounded-full bg-forest-50 text-forest-800 flex items-center justify-center text-base shrink-0 transition-transform duration-200"
                                :class="openFaq === {{ $index }} ? 'rotate-45' : ''"
                            >
                                +
                            </span>
                        </button>
                        <div
                            x-show="openFaq === {{ $index }}"
                            x-cloak
                            x-collapse
                            class="px-5 pb-5 text-sm text-ink-700 border-t border-line-100 leading-relaxed prose prose-sm max-w-none pt-3"
                        >
                            {!! $faq->answer !!}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Section 9: Lokasi Tembalang & Tempat Terdekat (FE-014) --}}
    <section id="lokasi" class="py-20 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        <div class="text-center max-w-3xl mx-auto mb-14">
            <span class="text-xs font-bold uppercase tracking-wider text-forest-700">Strategis & Dekat</span>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-forest-900 mt-1">Lokasi di Tembalang, Semarang</h2>
            <p class="mt-2 text-sm text-ink-600">Akses mudah ke lingkungan kampus, rumah sakit, kuliner malam, dan fasilitas harian.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-start">
            <!-- Left: Nearby POI list -->
            <div>
                <h3 class="text-lg font-bold text-forest-900 mb-4">Tempat Terdekat Sekitar Kost</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @forelse($nearbyPlaces as $place)
                        <div class="p-4 rounded-2xl border border-line-200 bg-white shadow-xs flex items-center justify-between">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-forest-700">{{ $place->category }}</span>
                                <h4 class="font-bold text-forest-900 text-sm mt-0.5">{{ $place->name }}</h4>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="num text-sm font-bold text-terracotta-600">{{ $place->distance_text }}</span>
                                @if($place->travel_minutes)
                                    <p class="num text-[11px] text-ink-500">~{{ $place->travel_minutes }} mnt</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="col-span-2 p-4 text-xs text-ink-500">
                            Lokasi dekat Universitas Diponegoro (UNDIP) & Politeknik Negeri Semarang.
                        </div>
                    @endforelse
                </div>

                <div class="mt-6 p-5 rounded-2xl bg-cream-100 border border-line-200 text-xs text-ink-700 space-y-1.5">
                    <p class="font-bold text-forest-900">Alamat Lengkap:</p>
                    <p>{{ $siteSettings['location']['address'] ?? 'Tembalang, Semarang, Jawa Tengah' }}</p>
                </div>
            </div>

            <!-- Right: Google Maps Embed -->
            <div class="rounded-3xl overflow-hidden border border-line-200 shadow-md bg-white min-h-[350px]">
                @if(!empty($siteSettings['location']['google_maps_embed']))
                    <div class="w-full h-full min-h-[350px]">
                        {!! $siteSettings['location']['google_maps_embed'] !!}
                    </div>
                @else
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15838.749666060155!2d110.4357917!3d-7.0519965!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e708c02826a5757%3A0xb3574c3eb7254e2c!2sTembalang%2C%20Semarang%20City%2C%20Central%20Java!5e0!3m2!1sen!2sid!4v1700000000000!5m2!1sen!2sid"
                        width="100%"
                        height="380"
                        style="border:0;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                    ></iframe>
                @endif
            </div>
        </div>
    </section>
</main>
@endsection
