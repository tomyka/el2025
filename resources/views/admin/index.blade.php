@extends('admin.layouts.master')
@section('content')

<div class="row g-3">

    <div class="col-12">
        <div class="sb-card-title"><i class="bi bi-gear-fill sb-card-icon"></i> Admin skydelis</div>
    </div>

    @php
    $sections = [
        ['icon' => 'bi-trophy',          'label' => 'Rezultatai (turas)',  'route' => 'admin.results',               'super' => false],
        ['icon' => 'bi-trophy-fill',     'label' => 'Visi rezultatai',     'route' => 'admin.resultsAll',            'super' => false],
        ['icon' => 'bi-calendar3',       'label' => 'Varžybos',            'route' => 'admin.games',                 'super' => false],
        ['icon' => 'bi-bar-chart-fill',  'label' => 'Eigos taškai',        'route' => 'admin.updateStandingPoints',  'super' => true],
        ['icon' => 'bi-people-fill',     'label' => 'Vartotojai',          'route' => 'admin.users',                 'super' => true],
        ['icon' => 'bi-flag-fill',       'label' => 'Komandos',            'route' => 'admin.teams',                 'super' => true],
        ['icon' => 'bi-calendar-event',  'label' => 'Turai',               'route' => 'admin.events',                'super' => true],
        ['icon' => 'bi-chat-left-text',  'label' => 'Žinutės',             'route' => 'admin.messages',              'super' => true],
    ];
    @endphp

    @foreach($sections as $s)
    @if(!$s['super'] || session('admin') >= 5)
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
