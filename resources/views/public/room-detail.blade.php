@extends('public.layouts.base')

@section('title', $roomType->name . ' — ' . ($settings['kost_name'] ?? 'ASRI Boarding House'))
@section('meta_description', Str::limit(strip_tags($roomType->description ?? $roomType->name), 150))

@section('content')
<main class="min-h-screen py-12 px-6 sm:px-12 max-w-5xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('public.home') }}#kamar" class="text-sm font-semibold text-forest-700 hover:text-forest-900 inline-flex items-center gap-1">
            ← Kembali ke Beranda
        </a>
    </div>

    <div class="bg-white rounded-3xl border border-line-200 overflow-hidden shadow-sm">
        {{-- Gallery Grid / Hero Image --}}
        @if($roomType->images->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 p-2 bg-cream-50">
                <div class="aspect-video md:aspect-auto md:h-96 rounded-2xl overflow-hidden">
                    <img src="{{ asset('storage/' . $roomType->images->first()->image_path) }}" alt="{{ $roomType->name }}" class="w-full h-full object-cover">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    @foreach($roomType->images->skip(1)->take(4) as $img)
                        <div class="aspect-video rounded-xl overflow-hidden">
                            <img src="{{ asset('storage/' . $img->image_path) }}" alt="{{ $roomType->name }}" class="w-full h-full object-cover">
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="w-full h-64 bg-forest-100 flex items-center justify-center text-forest-800 font-bold text-xl">
                {{ $roomType->name }}
            </div>
        @endif

        <div class="p-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-line-200 pb-6">
                <div>
                    <span class="text-xs uppercase font-bold tracking-wider text-forest-700">
                        {{ strtoupper($roomType->room_class->value ?? 'STANDARD') }} • <span class="num">{{ $roomType->size_m2 }} m²</span>
                    </span>
                    <h1 class="text-3xl font-extrabold text-forest-900 mt-1">{{ $roomType->name }}</h1>
                </div>
                <div class="text-left md:text-right">
                    <span class="text-xs text-ink-500 block">Harga Sewa Bulanan</span>
                    <span class="num text-2xl font-bold text-terracotta-600">
                        Rp {{ number_format($roomType->monthly_rate, 0, ',', '.') }}<span class="text-sm font-normal text-ink-500">/bulan</span>
                    </span>
                </div>
            </div>

            <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="md:col-span-2">
                    <h2 class="text-lg font-bold text-forest-900 mb-3">Deskripsi Kamar</h2>
                    <div class="prose text-ink-700 text-sm leading-relaxed">
                        {!! nl2br(e($roomType->description)) !!}
                    </div>

                    @if(!empty($roomType->facilities_json) && is_array($roomType->facilities_json))
                        <h2 class="text-lg font-bold text-forest-900 mt-8 mb-3">Fasilitas Dalam Kamar</h2>
                        <ul class="grid grid-cols-2 gap-2 text-sm text-ink-700">
                            @foreach($roomType->facilities_json as $fac)
                                <li class="flex items-center gap-2">
                                    <span class="text-forest-600">✓</span>
                                    <span>{{ is_string($fac) ? $fac : ($fac['name'] ?? '') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="bg-cream-50 p-6 rounded-2xl border border-line-200">
                    <h3 class="font-bold text-forest-900 mb-4">Opsi Paket Sewa</h3>
                    <div class="space-y-3 text-sm">
                        @if($roomType->daily_rate)
                            <div class="flex justify-between items-center py-2 border-b border-line-200">
                                <span class="text-ink-600">Harian</span>
                                <span class="num font-bold text-forest-900">Rp {{ number_format($roomType->daily_rate, 0, ',', '.') }}/malam</span>
                            </div>
                        @endif
                        @if($roomType->weekly_rate)
                            <div class="flex justify-between items-center py-2 border-b border-line-200">
                                <span class="text-ink-600">Mingguan</span>
                                <span class="num font-bold text-forest-900">Rp {{ number_format($roomType->weekly_rate, 0, ',', '.') }}/minggu</span>
                            </div>
                        @endif
                        <div class="flex justify-between items-center py-2 border-b border-line-200">
                            <span class="text-ink-600">Bulanan</span>
                            <span class="num font-bold text-forest-900">Rp {{ number_format($roomType->monthly_rate, 0, ',', '.') }}/bulan</span>
                        </div>
                    </div>

                    <a href="/login" class="mt-6 block w-full py-3 text-center bg-terracotta-500 hover:bg-terracotta-600 text-white font-medium rounded-xl shadow-md transition-colors">
                        Pesan Sekarang
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
