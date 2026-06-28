@extends('layouts.master')
@section('content')

@auth
    <div class="sb-card">
        @include('partials.fee')
        @include('partials.messages')
        @include('partials.warnings')
    </div>

    <div class="row g-3">
        @if(session('eventID') != 0)
        <div class="col-xl-6 col-lg-6 col-12">
            @include('partials.games')
        </div>
        @endif

        <div class="{{ session('eventID') != 0 ? 'col-xl-6 col-lg-6 col-12' : 'col-12' }}">
            <div class="sb-tabs-nav">
                <button class="sb-tab-btn active" data-tab="main-tab-pts">
                    <i class="bi bi-trophy-fill"></i> Taškai
                </button>
                <button class="sb-tab-btn" data-tab="main-tab-eiga">
                    <i class="bi bi-bar-chart-steps"></i> Eigos taškai
                </button>
            </div>
            <div id="main-tab-pts" class="sb-tab-pane">
                @include('partials.points')
            </div>
            <div id="main-tab-eiga" class="sb-tab-pane" style="display:none">
                @if(($firstGameStarted ?? false) && !empty($standings))
                    @include('partials.standings')
                @endif
                @include('partials.pointsStandings')
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.sb-tab-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var target = this.dataset.tab;
                document.querySelectorAll('.sb-tab-btn').forEach(function (b) { b.classList.remove('active'); });
                document.querySelectorAll('.sb-tab-pane').forEach(function (p) { p.style.display = 'none'; });
                this.classList.add('active');
                document.getElementById(target).style.display = '';
            });
        });
    });
    </script>

@else
    @include('welcome')
    @include('modals.main')
@endauth

@endsection
