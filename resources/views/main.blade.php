@extends('layouts.master')
@section('content')

@auth
    <div class="sb-card">
        @include('partials.fee')
        @include('partials.messages')
        @include('partials.warnings')
    </div>

    <div class="row g-3">
        @if(session('eventRate') == 2)
            <div class="col-xl-5 col-lg-6 col-12">
        @else
            <div class="col-xl-6 col-lg-6 col-12">
        @endif
            @if(session('eventID') != 0)
                @include('partials.games')
                @if($firstGameStarted ?? false)
                    @include('partials.standings')
                @endif
            @else
                @include('partials.points')
            @endif
        </div>

        @if(session('eventRate') == 2)
            <div class="col-xl-4 col-lg-6 col-12">
        @else
            <div class="col-xl-6 col-lg-6 col-12">
        @endif
            @if(session('eventID') != 0)
                @include('partials.points')
            @else
                @include('partials.pointsStandings')
            @endif
        </div>

        @if(session('eventRate') == 2)
            <div class="col-xl-3 col-lg-6 col-12">
                @include('partials.pointsStandings')
            </div>
        @endif
    </div>

    @if(($firstGameStarted ?? false) && isset($rankHistory) && count($rankHistory) >= 2)
    <div class="row mt-3">
        <div class="col-12">
            @include('partials.positionTrend')
        </div>
    </div>
    @endif
@else
    @include('welcome')
@endauth

@endsection
