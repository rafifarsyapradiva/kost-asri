@extends('public.layouts.base')

@section('title', 'ASRI Boarding House — Tembalang, Semarang')

@section('content')
    <main class="mx-auto flex min-h-screen max-w-4xl flex-col justify-center px-6 py-16">
        <section
            x-data="{ totalRooms: 32, floors: 2 }"
            class="rounded-3xl border border-line-200 bg-white p-8 shadow-sm"
        >
            <p class="text-sm font-semibold tracking-wide text-forest-700 uppercase">
                Tembalang, Semarang
            </p>
            <h1 class="mt-2 text-3xl font-bold text-forest-900 sm:text-4xl">
                ASRI Boarding House
            </h1>
            <p class="mt-3 text-base text-ink-600">
                Hunian kost nyaman dengan <span class="num font-semibold text-ink-900" x-text="totalRooms">32</span>
                kamar di <span class="num font-semibold text-ink-900" x-text="floors">2</span> lantai.
            </p>
        </section>
    </main>
@endsection

