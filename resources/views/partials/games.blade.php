<div class="sb-card" x-data="predModal()">
    <div class="sb-card-title d-flex justify-content-between align-items-center">
        <span><i class="bi bi-calendar3"></i> Artimiausios rungtynės</span>
        <a href="{{ route('prediction.results') }}" class="upcoming-all-link">Visi spėjimai <i class="bi bi-arrow-right-short"></i></a>
    </div>
    <div class="upcoming-list">
        @foreach($predictionGames as $predictionGame)
        @php
            $g       = $predictionGame['gameDetails'];
            $played  = $g->home_team_score !== null;
            $started = \Carbon\Carbon::parse($g->game_date, 'UTC')->isPast();
            $canPred = !$played && !$started && isset($g->prediction_id);
        @endphp
        <a href="{{ route('prediction.results') }}"
           class="upcoming-row {{ $canPred ? 'upcoming-row-pred' : '' }}"
           data-can-pred="{{ $canPred ? '1' : '0' }}"
           data-game-id="{{ $g->id }}"
           data-pred-id="{{ $g->prediction_id ?? '' }}"
           data-home="{{ $g->home_team }}"
           data-away="{{ $g->away_team }}"
           data-hscore="{{ $g->p_home_team_score ?? '' }}"
           data-ascore="{{ $g->p_away_team_score ?? '' }}"
           data-winner="{{ $g->game_winner_id ?? '' }}"
           data-game-date="{{ ucfirst(\Carbon\Carbon::parse($g->game_date, 'UTC')->setTimezone('Europe/Vilnius')->locale('lt')->isoFormat('MMMM D')) }} · {{ \Carbon\Carbon::parse($g->game_date, 'UTC')->setTimezone('Europe/Vilnius')->format('H:i') }}"
           x-on:click.prevent="navClick($event.currentTarget)"
           x-on:dblclick.prevent="rowClick($event.currentTarget)">
            <span class="upcoming-date">
                <span>{{ ucfirst(\Carbon\Carbon::parse($g->game_date, 'UTC')->setTimezone('Europe/Vilnius')->locale('lt')->isoFormat('MMM D')) }}</span>
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

            @php
                $streak   = $played ? (float)($g->streak_bonus ?? 0) : 0;
                $totalPts = $played ? (float)$g->full_points + $streak : 0;
                $popContent = '';
                if ($played && $totalPts > 0) {
                    $popContent = "<div class='sr-pop'>"
                        . "<div class='sr-pop-row'><span>Nugalėtojas</span><strong>" . number_format($g->winner_points, 1) . "</strong></div>"
                        . "<div class='sr-pop-row'><span>Skirtumas</span><strong>" . number_format($g->difference_points, 1) . "</strong></div>"
                        . "<div class='sr-pop-row'><span>Tikslus</span><strong>" . number_format($g->bingo_points, 1) . "</strong></div>"
                        . ($streak > 0 ? "<div class='sr-pop-row'><span>Serija</span><strong>+" . number_format($streak, 1) . "</strong></div>" : "")
                        . "</div>";
                }
            @endphp
            <span class="upcoming-pts {{ $played && $totalPts > 0 ? 'upt-scored' : 'upt-empty' }}"
                @if($played && $totalPts > 0)
                    data-bs-toggle="popover"
                    data-bs-trigger="hover"
                    data-bs-html="true"
                    data-bs-placement="left"
                    data-bs-content="{{ $popContent }}"
                @endif
            >
                {{ $played ? number_format($totalPts, 1) : '' }}
                @if($streak > 0)
                    <span class="upt-streak"><i class="bi bi-fire"></i>+{{ number_format($streak, 1) }}</span>
                @endif
            </span>
        </a>
        @endforeach
    </div>

    {{-- Single-game prediction modal --}}
    <div class="modal fade" id="gamePredModal" tabindex="-1">
        <div class="modal-dialog" style="max-width:450px">
            <div class="modal-content" style="border-radius:16px;overflow:hidden">
                <div class="modal-header border-0 pt-3 pb-0 pe-3">
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-1 pb-4 px-4">
                    <div class="pred-game">
                        <div class="pred-team-home">
                            <span class="pred-team-name" x-text="homeTeam"></span>
                            <img :src="homeTeam ? '/img/teams/' + homeTeam.toLowerCase().replace(/ /g, '%20') + '.svg' : 'data:,'"
                                 class="pred-flag" :alt="homeTeam">
                        </div>
                        <div class="pred-scores">
                            <span class="pred-time" x-text="gameDate"></span>
                            <div class="pred-scores-inputs">
                                <input type="text" x-model="homeScore"
                                       class="form-control pred-score"
                                       maxlength="2" autocomplete="off" placeholder="?">
                                <span class="pred-sep">:</span>
                                <input type="text" x-model="awayScore"
                                       class="form-control pred-score"
                                       maxlength="2" autocomplete="off" placeholder="?">
                            </div>
                        </div>
                        <div class="pred-team-away">
                            <img :src="awayTeam ? '/img/teams/' + awayTeam.toLowerCase().replace(/ /g, '%20') + '.svg' : 'data:,'"
                                 class="pred-flag" :alt="awayTeam">
                            <span class="pred-team-name" x-text="awayTeam"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.upcoming-pts[data-bs-toggle="popover"]').forEach(function (el) {
        new bootstrap.Popover(el, { container: 'body', html: true });
    });
});

function predModal() {
    return {
        gameID:       null,
        predictionID: null,
        homeTeam:     '',
        awayTeam:     '',
        homeScore:    '',
        awayScore:    '',
        winnerId:     null,
        gameDate:     '',
        saving:       false,

        init() {
            document.getElementById('gamePredModal').addEventListener('hidden.bs.modal', () => {
                this.save();
            });
        },

        navClick(el) {
            if (el.dataset.canPred !== '1') {
                window.location = '{{ route('prediction.results') }}';
            }
        },

        rowClick(el) {
            if (el.dataset.canPred !== '1') return;
            const d = el.dataset;
            this.open(
                parseInt(d.gameId),
                parseInt(d.predId),
                d.home,
                d.away,
                d.hscore !== '' ? parseInt(d.hscore) : null,
                d.ascore !== '' ? parseInt(d.ascore) : null,
                d.winner !== '' ? parseInt(d.winner) : null,
                d.gameDate || ''
            );
        },

        open(gameID, predictionID, homeTeam, awayTeam, homeScore, awayScore, winnerId, gameDate) {
            this.gameID       = gameID;
            this.predictionID = predictionID;
            this.homeTeam     = homeTeam;
            this.awayTeam     = awayTeam;
            this.homeScore    = homeScore !== null ? String(homeScore) : '';
            this.awayScore    = awayScore !== null ? String(awayScore) : '';
            this.winnerId     = winnerId;
            this.gameDate     = gameDate;
            this.saving       = false;
            const modalEl = document.getElementById('gamePredModal');
            (bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl)).show();
        },

        async save() {
            if (this.saving || this.gameID === null) return;

            const homeVal = (this.homeScore ?? '').trim();
            const awayVal = (this.awayScore ?? '').trim();
            const bothFilled = homeVal !== '' && awayVal !== '';

            if (!bothFilled) return;

            this.saving = true;
            const token = document.querySelector('meta[name="csrf-token"]').content;
            const fd = new FormData();
            fd.append('_token',            token);
            fd.append('gameID',            this.gameID);
            fd.append('prediction_gameID', this.predictionID);
            fd.append('homeTeamScore',     homeVal);
            fd.append('awayTeamScore',     awayVal);
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
                        el.textContent = homeVal + ':' + awayVal;
                    }
                }
            } catch {
                // silent fail — background save
            } finally {
                this.saving = false;
            }
        }
    };
}
</script>
