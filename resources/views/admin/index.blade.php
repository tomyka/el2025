@extends('admin.layouts.master')
@section('content')

<div class="row g-3">

    <div class="col-12">
        <div class="sb-card-title"><i class="bi bi-gear-fill sb-card-icon"></i> Admin skydelis</div>
    </div>

    @php
    $sections = [
        ['icon' => 'bi-trophy',          'label' => 'Rezultatai (turas)',  'route' => 'admin.results'],
        ['icon' => 'bi-trophy-fill',     'label' => 'Visi rezultatai',     'route' => 'admin.resultsAll'],
        ['icon' => 'bi-people-fill',     'label' => 'Vartotojai',          'route' => 'admin.users'],
        ['icon' => 'bi-flag-fill',       'label' => 'Komandos',            'route' => 'admin.teams'],
        ['icon' => 'bi-calendar3',       'label' => 'Varžybos',            'route' => 'admin.games'],
        ['icon' => 'bi-calendar-event',  'label' => 'Turai',               'route' => 'admin.events'],
        ['icon' => 'bi-diagram-3-fill',  'label' => 'Grupės',              'route' => 'admin.groups'],
        ['icon' => 'bi-chat-left-text',  'label' => 'Žinutės',             'route' => 'admin.messages'],
        ['icon' => 'bi-gear-fill',       'label' => 'Nustatymai',          'route' => 'admin.settings'],
        ['icon' => 'bi-bar-chart-fill',  'label' => 'Eigos taškai',        'route' => 'admin.updateStandingPoints'],
    ];
    @endphp

    @foreach($sections as $s)
    <div class="col-xl-2 col-lg-3 col-md-4 col-6">
        <a href="{{ route($s['route']) }}" class="admin-tile">
            <i class="bi {{ $s['icon'] }} admin-tile-icon"></i>
            <span class="admin-tile-label">{{ $s['label'] }}</span>
        </a>
    </div>
    @endforeach

</div>

@endsection
