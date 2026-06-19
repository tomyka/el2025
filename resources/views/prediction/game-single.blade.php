@extends('layouts.master')
@section('content')

@if(session('info'))
<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('info') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="sb-card">
    <div class="sb-card-title">
        <i class="bi bi-pencil-square me-1"></i>Spėjimas
    </div>

    {{-- Teams --}}
    <div class="d-flex align-items-center justify-content-center gap-4 my-4">
        <div class="text-center">
            <img src="{{ asset('img/teams/' . str_replace(' ', '%20', strtolower($game->home_team->team)) . '.svg') }}"
                 width="52" height="52" alt="{{ $game->home_team->team }}">
            <div class="fw-semibold mt-2">{{ $game->home_team->team }}</div>
        </div>
        <div class="text-muted fw-bold fs-4">vs</div>
        <div class="text-center">
            <img src="{{ asset('img/teams/' . str_replace(' ', '%20', strtolower($game->away_team->team)) . '.svg') }}"
                 width="52" height="52" alt="{{ $game->away_team->team }}">
            <div class="fw-semibold mt-2">{{ $game->away_team->team }}</div>
        </div>
    </div>

    {{-- Kickoff time --}}
    <div class="text-center text-muted mb-4" style="font-size:.85rem">
        <i class="bi bi-clock me-1"></i>
        {{ \Carbon\Carbon::parse($game->game_date, 'UTC')->setTimezone('Europe/Vilnius')->format('Y-m-d H:i') }} LT
    </div>

    @if($locked)
        <div class="alert alert-secondary text-center">
            <i class="bi bi-lock-fill me-1"></i>Žaidimas jau prasidėjo — spėjimų keisti negalima.
        </div>
        @if($prediction && $prediction->home_team_score !== null)
        <div class="text-center mt-3">
            <span class="fs-3 fw-bold">{{ $prediction->home_team_score }} : {{ $prediction->away_team_score }}</span>
            <div class="text-muted mt-1" style="font-size:.82rem">Jūsų spėjimas</div>
        </div>
        @endif
    @elseif($prediction)
        <form id="sg-form">
            @csrf
            <input type="hidden" name="gameID" value="{{ $game->id }}">
            <input type="hidden" name="prediction_gameID" value="{{ $prediction->id }}">
            <input type="hidden" name="gameWinnerID" value="{{ $prediction->game_winner_id ?? '' }}">

            <div class="d-flex align-items-center justify-content-center gap-3 mb-4">
                <input type="number" id="sg-home" name="homeTeamScore"
                       class="form-control text-center fw-bold fs-5"
                       style="width:80px" min="0" max="99"
                       value="{{ $prediction->home_team_score ?? '' }}"
                       placeholder="?">
                <span class="fw-bold fs-4 text-muted">:</span>
                <input type="number" id="sg-away" name="awayTeamScore"
                       class="form-control text-center fw-bold fs-5"
                       style="width:80px" min="0" max="99"
                       value="{{ $prediction->away_team_score ?? '' }}"
                       placeholder="?">
            </div>
            <div class="text-center">
                <button type="submit" id="sg-btn" class="btn btn-primary px-5">
                    <i class="bi bi-check2 me-1"></i>Išsaugoti spėjimą
                </button>
            </div>
        </form>
        <script>
        document.getElementById('sg-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('sg-btn');
            btn.disabled = true;
            const token = document.querySelector('meta[name="csrf-token"]').content;
            const fd = new FormData(this);
            fd.set('_token', token);
            try {
                const res = await fetch('{{ url('prediction/results') }}', {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (res.ok) {
                    window.location = '{{ route("main") }}';
                } else {
                    btn.disabled = false;
                }
            } catch {
                btn.disabled = false;
            }
        });
        </script>
    @else
        <div class="alert alert-warning text-center">
            Spėjimas nerastas. Bandykite dar kartą nuo <a href="{{ route('main') }}">pagrindinio puslapio</a>.
        </div>
    @endif
</div>

@endsection
