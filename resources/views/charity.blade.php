@extends('layouts.master')
@section('content')

{{-- Hero --}}
<div class="ch-hero mb-4">
    <div class="ch-hero-text">
        <h1 class="ch-hero-title">{{ __('Žaidžiame dėl gero tikslo') }}</h1>
        <p class="ch-hero-sub">
            {{ __('SportBet — tai ne tik futbolo prognozių žaidimas. Nuo 2018 metų žaidėjai savanoriškai aukoja') }}
            <strong><a href="https://www.jaunimolinija.lt/" target="_blank" rel="noopener" class="ch-hero-link">{{ __('Jaunimo linijai') }}</a></strong> —
            {{ __('organizacijai, teikiančiai nemokamą psichologinę pagalbą jaunimui visoje Lietuvoje.') }}
        </p>
        <div class="ch-stat-row">
            <div class="ch-stat">
                <span class="ch-stat-value">7 500€</span>
                <span class="ch-stat-label">{{ __('paaukota iš viso') }}</span>
            </div>
            <div class="ch-stat">
                <span class="ch-stat-value">8+</span>
                <span class="ch-stat-label">{{ __('turnyrų') }}</span>
            </div>
            <div class="ch-stat">
                <span class="ch-stat-value">2018</span>
                <span class="ch-stat-label">{{ __('pradėjome') }}</span>
            </div>
        </div>
        <a href="https://www.jaunimolinija.lt/" target="_blank" rel="noopener" class="ch-cta">
            <i class="bi bi-heart-fill me-1"></i> {{ __('Jaunimo linija') }} &rarr;
        </a>
    </div>
    <div class="ch-hero-img-wrap">
        <a href="https://www.jaunimolinija.lt/" target="_blank" rel="noopener">
            <img src="{{ URL::to('img/JL_thank.png') }}" class="ch-hero-img" alt="{{ __('Jaunimo linija — ačiū') }}">
        </a>
    </div>
</div>

{{-- About --}}
<div class="sb-card mb-4">
    <p style="font-size:.93rem;line-height:1.7;margin:0;color:var(--sb-text)">
        {{ __('Noriu padėkoti visiems dalyvaujantiems ir skiriantiems lėšas labdarai. Jūsų dėka JAU pavyko surinkti ir paaukoti Jaunimo Linijai') }} <strong>7 500€</strong>.
        {{ __('Iki 2024 metų visą surinktą paramą dvigubino') }} <strong>TransUnion Lithuania</strong> {{ __('įmonė.') }}
        <strong>{{ __('Ačiū, kad prisidedate prie gerų darbų!') }}</strong>
    </p>
</div>

{{-- Payment evidence --}}
<div class="sb-card">
    <div class="sb-card-title mb-3">
        <i class="bi bi-receipt sb-card-icon"></i> {{ __('Mokėjimų įrodymai') }}
    </div>
    <p style="font-size:.83rem;color:var(--sb-muted);margin-bottom:20px">
        {{ __('Kiekvieno turnyro pabaigoje surinkta suma pervedama Jaunimo linijai. Žemiau — visi mokėjimų patvirtinimai. Spustelėk nuotrauką, kad pamatytum detaliau.') }}
    </p>

    @php
    $payments = [
        ['label' => 'Euroleague 2025',  'img' => 'JL_payment_el2025.png'],
        ['label' => 'Euroleague 2024',  'img' => 'JL_payment_el2024.png'],
        ['label' => 'Euroleague 2023',  'img' => 'JL_payment_el2023.png'],
        ['label' => 'Euroleague 2022',  'img' => 'JL_payment_el2022.png'],
        ['label' => 'EuroBasket 2022',  'img' => 'JL_payment_ecb2022.png'],
        ['label' => 'Euroleague 2021',  'img' => 'JL_payment_el2021.png'],
        ['label' => 'Euroleague 2020',  'img' => 'JL_payment_el2020.png'],
        ['label' => 'Euro 2020',        'img' => 'JL_payment_ecf2020.png'],
        ['label' => 'Euroleague 2019',  'img' => 'JL_payment_el2019.png'],
        ['label' => 'Euroleague 2018 (2)', 'img' => 'JL_payment_el2018_2.png'],
        ['label' => 'Euroleague 2018 (1)', 'img' => 'JL_payment_el2018_1.png'],
    ];
    @endphp

    <div class="ch-payments-grid">
        @foreach($payments as $p)
        <a href="{{ URL::to('img/' . $p['img']) }}" target="_blank" rel="noopener" class="ch-payment-card">
            <div class="ch-payment-label">{{ $p['label'] }}</div>
            <img src="{{ URL::to('img/' . $p['img']) }}" alt="{{ $p['label'] }} {{ __('mokėjimas') }}" class="ch-payment-img">
        </a>
        @endforeach
    </div>
</div>

@endsection
