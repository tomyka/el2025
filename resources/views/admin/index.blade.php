@extends('admin.layouts.master')
@section('content')

<div class="row g-3">

    <div class="col-12">
        <div class="sb-card-title"><i class="bi bi-gear-fill sb-card-icon"></i> {{ __('Admin skydelis') }}</div>
    </div>

    @php
    $sections = [
        ['icon' => 'bi-trophy',          'label' => __('Rezultatai (turas)'),          'route' => 'admin.results',               'super' => false],
        ['icon' => 'bi-trophy-fill',     'label' => __('Visi rezultatai'),              'route' => 'admin.resultsAll',            'super' => false],
        ['icon' => 'bi-calendar3',       'label' => __('Rungtynės'),                    'route' => 'admin.games',                 'super' => false],
        ['icon' => 'bi-people-fill',     'label' => __('Vartotojai'),                   'route' => 'admin.users',                 'super' => true],
        ['icon' => 'bi-flag-fill',       'label' => __('Komandos'),                     'route' => 'admin.teams',                 'super' => true],
        ['icon' => 'bi-calendar-event',  'label' => __('Turai'),                        'route' => 'admin.events',                'super' => true],
        ['icon' => 'bi-chat-left-text',  'label' => __('Žinutės'),                      'route' => 'admin.messages',              'super' => true],
        ['icon' => 'bi-bar-chart-fill',  'label' => __('Eigos taškai'),                 'route' => 'admin.updateStandingPoints',  'super' => true, 'minLevel' => 9],
        ['icon' => 'bi-trophy-fill',     'label' => __('Lygos'),                        'route' => 'admin.leagues',               'super' => false],
        ['icon' => 'bi-clock-history',   'label' => __('Auditas'),                      'route' => 'admin.audit',                 'super' => true],
        ['icon' => 'bi-globe2',          'label' => __('Turnyrai'),                     'route' => 'admin.tournaments',           'super' => true],
    ];
    @endphp

    @foreach($sections as $s)
    @if(session('admin') >= ($s['minLevel'] ?? ($s['super'] ? 5 : 1)))
    <div class="col-xl-2 col-lg-3 col-md-4 col-6">
        <a href="{{ route($s['route']) }}" class="admin-tile">
            <i class="bi {{ $s['icon'] }} admin-tile-icon"></i>
            <span class="admin-tile-label">{{ $s['label'] }}</span>
        </a>
    </div>
    @endif
    @endforeach


</div>

@endsection
