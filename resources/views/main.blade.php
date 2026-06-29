@extends('layouts.master')
@section('content')

@auth
    @include('partials.progress-bar')

    {{-- Fee / messages / warnings notice card --}}
    @php $showNotices = !empty(session('fee')) || session()->has('info') || session()->has('error') || ($standingsMissing ?? false); @endphp
    @if($showNotices)
    <div class="sb-card mb-3">
        @include('partials.fee')
        @include('partials.messages')
        @include('partials.warnings')
    </div>
    @endif

    <div class="row g-3 align-items-start">

        {{-- PRIMARY COLUMN (60%): upcoming games + leaderboard --}}
        <div class="col-lg-7 col-12">
            @if(session('eventID') != 0)
                @include('partials.games')
            @endif
            @include('partials.points')
        </div>

        {{-- SIDEBAR COLUMN (40%): snapshot + standings + activity --}}
        <div class="col-lg-5 col-12">
            @include('partials.snapshot-card')
            @if(($firstGameStarted ?? false) && !empty($standings))
                @include('partials.standings')
            @endif
            {{-- @include('partials.pointsStandings') --}}
            @include('partials.activity-feed')
        </div>

    </div>

@else
    @include('welcome')
    @include('modals.main')
@endauth

@endsection
