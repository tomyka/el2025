<div class="sb-card" x-data="predModal()">
    <div class="sb-card-title d-flex justify-content-between align-items-center">
        <span><i class="bi bi-calendar3"></i> Artimiausios rungtynės</span>
        <a href="{{ route('prediction.results') }}" class="upcoming-all-link">Visi spėjimai <i class="bi bi-arrow-right-short"></i></a>
    </div>
    <div class="upcoming-list">
        @foreach($predictionGames as $predictionGame)
        @php
            $g      = $predictionGame['gameDetails'];
            $played = $g->home_team_score !== null;
            $canPred = !$played && isset($g->prediction_id);
        @endphp
        <a href="{{ route('prediction.results') }}" class="upcoming-row">
            <span class="upcoming-date">
                <span>{{ \Carbon\Carbon::parse($g->game_date, 'UTC')->setTimezone('Europe/Vilnius')->format('d.m') }}</span>
                <span class="upcoming-time">{{ \Carbon\Carbon::parse($g->game_date, 'UTC')->setTimezone('Europe/Vilnius')->format('H:i') }}</span>
            </span>

            <span class="upcoming-team upcoming-home">
                <img src="{{ asset('img/teams/' . str_replace(' ', '%20', strtolower($g->home_team)) . '.svg') }}"
                     class="upcoming-flag" alt="{{ $g->home_team }}">
                <span class="upcoming-name d-none d-md-inline">{{ $g->home_team }}</span>
            </span>

            <span class="upcoming-scores">
                @if($played)
                    <span class="usc-actual">{{ $g->home_team_score }}:{{ $g->away_team_score }}</span>
                    <span class="usc-sep">/</span>
                @endif
                <span id="usc-pred-{{ $g->id }}" class="usc-pred {{ $played ? '' : 'usc-pred-only' }}">{{ $g->p_home_team_score ?? '?' }}:{{ $g->p_away_team_score ?? '?' }}</span>
            </span>

            <span class="upcoming-team upcoming-away">
                <span class="upcoming-name d-none d-md-inline">{{ $g->away_team }}</span>
                <img src="{{ asset('img/teams/' . str_replace(' ', '%20', strtolower($g->away_team)) . '.svg') }}"
                     class="upcoming-flag" alt="{{ $g->away_team }}">
            </span>

            @php $streak = $played ? ($g->streak_bonus ?? 0) : 0; @endphp
            <span class="upcoming-pts {{ $played && ($g->full_points + $streak) > 0 ? 'upt-scored' : 'upt-empty' }}">
                {{ $played ? number_format($g->full_points + $streak, 1) : '' }}
                @if($streak > 0)
                    <span class="upt-streak"><i class="bi bi-fire"></i>+{{ number_format($streak, 1) }}</span>
                @endif
            </span>

            @if($canPred)
            <button type="button"
                    class="btn btn-sm btn-outline-primary upcoming-pred-btn"
                    title="Prognozuoti"
                    @click.prevent.stop="open(
                        {{ $g->id }},
                        {{ $g->prediction_id }},
                        {{ json_encode($g->home_team) }},
                        {{ json_encode($g->away_team) }},
                        {{ $g->p_home_team_score ?? 'null' }},
                        {{ $g->p_away_team_score ?? 'null' }},
                        {{ $g->game_winner_id ?? 'null' }}
                    )">
                <i class="bi bi-pencil-fill"></i>
            </button>
            @endif
        </a>
        @endforeach
    </div>

    {{-- Single-game prediction modal --}}
    <div class="modal fade" id="gamePredModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header border-0 pb-1">
                    <span class="fw-semibold" x-text="homeTeam + ' vs ' + awayTeam"></span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3">
                    <div class="d-flex align-items-center justify-content-center gap-3">
                        <input type="number" x-model="homeScore"
                               class="form-control text-center fw-bold fs-5"
                               style="width:72px" min="0" max="99" placeholder="?">
                        <span class="fw-bold fs-4 text-muted">:</span>
                        <input type="number" x-model="awayScore"
                               class="form-control text-center fw-bold fs-5"
                               style="width:72px" min="0" max="99" placeholder="?">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-primary w-100" :disabled="saving" @click="save()">
                        <span x-show="!saving"><i class="bi bi-check2 me-1"></i>Išsaugoti</span>
                        <span x-show="saving">Saugoma...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function predModal() {
    return {
        gameID:       null,
        predictionID: null,
        homeTeam:     '',
        awayTeam:     '',
        homeScore:    null,
        awayScore:    null,
        winnerId:     null,
        saving:       false,

        open(gameID, predictionID, homeTeam, awayTeam, homeScore, awayScore, winnerId) {
            this.gameID       = gameID;
            this.predictionID = predictionID;
            this.homeTeam     = homeTeam;
            this.awayTeam     = awayTeam;
            this.homeScore    = homeScore;
            this.awayScore    = awayScore;
            this.winnerId     = winnerId;
            this.saving       = false;
            bootstrap.Modal.getOrCreateInstance(
                document.getElementById('gamePredModal')
            ).show();
        },

        async save() {
            this.saving = true;
            const token = document.querySelector('meta[name="csrf-token"]').content;
            const fd = new FormData();
            fd.append('_token',            token);
            fd.append('gameID',            this.gameID);
            fd.append('prediction_gameID', this.predictionID);
            fd.append('homeTeamScore',     this.homeScore ?? '');
            fd.append('awayTeamScore',     this.awayScore ?? '');
            fd.append('gameWinnerID',      this.winnerId ?? '');

            try {
                const res = await fetch('{{ url('prediction/results') }}', {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });

                if (res.ok) {
                    const el = document.getElementById('usc-pred-' + this.gameID);
                    if (el) {
                        const h = this.homeScore !== null ? this.homeScore : '?';
                        const a = this.awayScore !== null ? this.awayScore : '?';
                        el.textContent = h + ':' + a;
                    }
                    bootstrap.Modal.getInstance(
                        document.getElementById('gamePredModal')
                    ).hide();
                } else {
                    alert('Spėjimas nepavyko išsaugoti. Bandykite dar kartą.');
                }
            } catch {
                alert('Tinklo klaida. Bandykite dar kartą.');
            } finally {
                this.saving = false;
            }
        }
    };
}
</script>
