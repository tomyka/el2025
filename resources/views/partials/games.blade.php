<div class="sb-card" x-data="predModal()">
    <div class="sb-card-title d-flex justify-content-between align-items-center">
        <span><i class="bi bi-calendar3"></i> Artimiausios rungtynės</span>
        <a href="{{ route('prediction.results') }}" class="upcoming-all-link">Visi spėjimai <i class="bi bi-arrow-right-short"></i></a>
    </div>
    <div class="upcoming-list">
        @foreach($predictionGames as $predictionGame)
        @php
            $g          = $predictionGame['gameDetails'];
            $played     = $g->home_team_score !== null;
            $started    = \Carbon\Carbon::parse($g->game_date, 'UTC')->isPast();
            $canPred    = !$played && !$started && isset($g->prediction_id);
            $isPredDraw = $g->p_home_team_score !== null && $g->p_away_team_score !== null
                          && (string)$g->p_home_team_score === (string)$g->p_away_team_score;
            $penWinner  = ($g->is_knockout && $isPredDraw && !empty($g->game_winner_id))
                          ? (int)$g->game_winner_id : null;
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
           data-is-knockout="{{ $g->is_knockout ? '1' : '0' }}"
           data-home-id="{{ $g->home_team_id }}"
           data-away-id="{{ $g->away_team_id }}"
           data-penalty-winner="{{ $g->game_winner_id ?? '' }}"
           data-home-odds="{{ $g->home_odds ?? 0 }}"
           data-draw-odds="{{ $g->draw_odds ?? 0 }}"
           data-away-odds="{{ $g->away_odds ?? 0 }}"
           data-rate="{{ $g->rate ?? 1 }}"
           x-on:click.prevent
           x-on:dblclick.prevent="rowClick($event.currentTarget)">
            <span class="upcoming-date">
                <span>{{ ucfirst(\Carbon\Carbon::parse($g->game_date, 'UTC')->setTimezone('Europe/Vilnius')->locale('lt')->isoFormat('MMM D')) }}</span>
                <span class="upcoming-time">{{ \Carbon\Carbon::parse($g->game_date, 'UTC')->setTimezone('Europe/Vilnius')->format('H:i') }}</span>
            </span>

            <span class="upcoming-team upcoming-home">
                <img src="{{ asset('img/teams/' . str_replace(' ', '%20', strtolower($g->home_team)) . '.svg') }}"
                     class="upcoming-flag" alt="{{ $g->home_team }}">
                <span class="upcoming-name d-none d-md-inline">{{ $g->home_team }}</span>
                @if($penWinner === (int)$g->home_team_id)
                <i class="bi bi-check-circle-fill upcoming-pw-badge"></i>
                @endif
            </span>

            <span class="upcoming-scores">
                @if($played)
                    <span class="usc-actual">{{ $g->home_team_score }}:{{ $g->away_team_score }}</span>
                    <span class="usc-sep">/</span>
                @endif
                <span id="usc-pred-{{ $g->id }}" class="usc-pred {{ $played ? '' : 'usc-pred-only' }}">{{ $g->p_home_team_score ?? '?' }}:{{ $g->p_away_team_score ?? '?' }}</span>
            </span>

            <span class="upcoming-team upcoming-away">
                @if($penWinner === (int)$g->away_team_id)
                <i class="bi bi-check-circle-fill upcoming-pw-badge"></i>
                @endif
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
                        . "<div class='sr-pop-row'><span>Serija</span><strong>" . ($streak > 0 ? '+' : '') . number_format($streak, 1) . "</strong></div>"
                        . "</div>";
                }
            @endphp
            @php $hasRowOdds = $canPred && $g->p_home_team_score !== null && $g->p_away_team_score !== null; @endphp
            <span class="upcoming-pts {{ $played && $totalPts > 0 ? 'upt-scored' : 'upt-empty' }}"
                @if($played && $totalPts > 0)
                    data-bs-toggle="popover"
                    data-bs-trigger="hover"
                    data-bs-html="true"
                    data-bs-placement="left"
                    data-bs-content="{{ $popContent }}"
                @endif
            >
                @if($played)
                    {{ number_format($totalPts, 1) }}
                    @if($streak > 0)
                        <span class="upt-streak"><i class="bi bi-fire"></i>+{{ number_format($streak, 1) }}</span>
                    @endif
                @elseif($hasRowOdds)
                    @php
                    $oHomeWinPts = round((1 + ($g->home_odds ?? 0)) * 5 * ($g->rate ?? 1), 1);
                    $oDrawPts    = round((1 + ($g->draw_odds ?? 0)) * 5 * ($g->rate ?? 1), 1);
                    $oAwayWinPts = round((1 + ($g->away_odds ?? 0)) * 5 * ($g->rate ?? 1), 1);
                    $oddsPopContent = "<div class='sr-pop'>"
                        . "<div class='sr-pop-row'><span>" . $g->home_team . "</span><strong>+" . number_format($oHomeWinPts, 1) . " pt</strong></div>"
                        . "<div class='sr-pop-row'><span>Lygiosios</span><strong>+" . number_format($oDrawPts, 1) . " pt</strong></div>"
                        . "<div class='sr-pop-row'><span>" . $g->away_team . "</span><strong>+" . number_format($oAwayWinPts, 1) . " pt</strong></div>"
                        . "</div>";
                    @endphp
                    <span class="pred-odds-toggle upcoming-odds-pop"
                          data-bs-toggle="popover"
                          data-bs-trigger="hover"
                          data-bs-html="true"
                          data-bs-placement="left"
                          data-bs-content="{{ $oddsPopContent }}"
                          @click.stop>
                        <i class="bi bi-graph-up-arrow"></i>
                    </span>
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
                            <img :src="homeTeam ? '/img/teams/' + homeTeam.toLowerCase().replace(/ /g, '%20') + '.svg' : 'data:,'"
                                 class="pred-flag" :alt="homeTeam">
                            <span class="pred-team-name" x-text="homeTeam"></span>
                            <button type="button" class="pred-pw-check"
                                    x-show="isKnockout && homeScore !== '' && awayScore !== '' && homeScore === awayScore"
                                    :class="{ active: penaltyWinner === homeId }"
                                    @click="penaltyWinner = homeId">
                                <i class="bi bi-check-circle-fill"></i>
                            </button>
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
                            <button type="button" class="pred-pw-check"
                                    x-show="isKnockout && homeScore !== '' && awayScore !== '' && homeScore === awayScore"
                                    :class="{ active: penaltyWinner === awayId }"
                                    @click="penaltyWinner = awayId">
                                <i class="bi bi-check-circle-fill"></i>
                            </button>
                            <span class="pred-team-name" x-text="awayTeam"></span>
                            <img :src="awayTeam ? '/img/teams/' + awayTeam.toLowerCase().replace(/ /g, '%20') + '.svg' : 'data:,'"
                                 class="pred-flag" :alt="awayTeam">
                        </div>
                    </div>

                    {{-- Odds panel (full width, below the grid) --}}
                    <div x-show="homeScore !== '' && awayScore !== ''" x-transition class="pred-odds-panel">
                        <div class="pred-odds-col">
                            <span class="pred-odds-label" x-text="homeTeam"></span>
                            <span class="pred-odds-pts" x-text="'+' + homeWinPts + ' pt'"></span>
                        </div>
                        <div class="pred-odds-col">
                            <span class="pred-odds-label">Lygiosios</span>
                            <span class="pred-odds-pts" x-text="'+' + drawPts + ' pt'"></span>
                        </div>
                        <div class="pred-odds-col">
                            <span class="pred-odds-label" x-text="awayTeam"></span>
                            <span class="pred-odds-pts" x-text="'+' + awayWinPts + ' pt'"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.upcoming-list [data-bs-toggle="popover"]').forEach(function (el) {
        new bootstrap.Popover(el, { html: true });
    });
});

function predModal() {
    return {
        gameID:        null,
        predictionID:  null,
        homeTeam:      '',
        awayTeam:      '',
        homeScore:     '',
        awayScore:     '',
        isKnockout:    false,
        homeId:        null,
        awayId:        null,
        penaltyWinner: null,
        gameDate:      '',
        saving:        false,
        showOdds:      false,
        hasOdds:       false,
        homeWinPts:    0,
        drawPts:       0,
        awayWinPts:    0,
        rate:          1,

        init() {
            const modalEl = document.getElementById('gamePredModal');
            modalEl.addEventListener('hide.bs.modal', () => {
                if (document.activeElement) document.activeElement.blur();
            });
            modalEl.addEventListener('hidden.bs.modal', () => {
                this.save();
            });
            let oddsDebounce;
            const debouncedSave = () => {
                clearTimeout(oddsDebounce);
                oddsDebounce = setTimeout(() => this.save(), 500);
            };
            this.$watch('homeScore', debouncedSave);
            this.$watch('awayScore', debouncedSave);
            this.$watch('penaltyWinner', debouncedSave);
        },

        navClick(el) {
            if (el.dataset.canPred !== '1') {
                window.location = '{{ route('prediction.results') }}';
            }
        },

        rowClickWithOdds(el) {
            if (el.dataset.canPred !== '1') return;
            const d = el.dataset;
            this.open(
                parseInt(d.gameId), parseInt(d.predId),
                d.home, d.away,
                d.hscore !== '' ? parseInt(d.hscore) : null,
                d.ascore !== '' ? parseInt(d.ascore) : null,
                d.gameDate || '',
                d.isKnockout === '1',
                d.homeId ? parseInt(d.homeId) : null,
                d.awayId ? parseInt(d.awayId) : null,
                d.penaltyWinner ? parseInt(d.penaltyWinner) : null,
                d.homeOdds || 0, d.drawOdds || 0, d.awayOdds || 0, d.rate || 1
            );
            this.showOdds = true;
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
                d.gameDate || '',
                d.isKnockout === '1',
                d.homeId ? parseInt(d.homeId) : null,
                d.awayId ? parseInt(d.awayId) : null,
                d.penaltyWinner ? parseInt(d.penaltyWinner) : null,
                d.homeOdds || 0,
                d.drawOdds || 0,
                d.awayOdds || 0,
                d.rate || 1
            );
        },

        open(gameID, predictionID, homeTeam, awayTeam, homeScore, awayScore, gameDate, isKnockout, homeId, awayId, penaltyWinner, homeOdds, drawOdds, awayOdds, rate) {
            this.gameID        = gameID;
            this.predictionID  = predictionID;
            this.homeTeam      = homeTeam;
            this.awayTeam      = awayTeam;
            this.homeScore     = homeScore !== null ? String(homeScore) : '';
            this.awayScore     = awayScore !== null ? String(awayScore) : '';
            this.gameDate      = gameDate;
            this.isKnockout    = isKnockout;
            this.homeId        = homeId;
            this.awayId        = awayId;
            this.penaltyWinner = penaltyWinner;
            this.saving        = false;
            this.showOdds      = false;
            this.rate          = parseFloat(rate) || 1;
            this._updateOddsDisplay(parseFloat(homeOdds || 0), parseFloat(drawOdds || 0), parseFloat(awayOdds || 0));
            const modalEl = document.getElementById('gamePredModal');
            (bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl)).show();
        },

        _updateOddsDisplay(homeOdds, drawOdds, awayOdds) {
            const r = this.rate;
            this.homeWinPts = Math.round((1 + homeOdds) * 5 * r * 10) / 10;
            this.drawPts    = Math.round((1 + drawOdds) * 5 * r * 10) / 10;
            this.awayWinPts = Math.round((1 + awayOdds) * 5 * r * 10) / 10;
            this.hasOdds    = (homeOdds > 0 || drawOdds > 0 || awayOdds > 0);
        },

        async save() {
            if (this.saving || this.gameID === null) return;

            const homeVal    = (this.homeScore ?? '').trim();
            const awayVal    = (this.awayScore ?? '').trim();
            const bothFilled = homeVal !== '' && awayVal !== '';
            const isDraw     = bothFilled && homeVal === awayVal;

            if (!bothFilled) return;
            if (this.isKnockout && isDraw && !this.penaltyWinner) return;

            this.saving = true;
            const token = document.querySelector('meta[name="csrf-token"]').content;
            const fd = new FormData();
            fd.append('_token',            token);
            fd.append('gameID',            this.gameID);
            fd.append('prediction_gameID', this.predictionID);
            fd.append('homeTeamScore',     homeVal);
            fd.append('awayTeamScore',     awayVal);
            fd.append('gameWinnerID',      (!isDraw || !this.penaltyWinner) ? '' : this.penaltyWinner);

            try {
                const res = await fetch('{{ url('prediction/results') }}', {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });

                if (res.ok) {
                    const data = await res.json();

                    // Update score display
                    const el = document.getElementById('usc-pred-' + this.gameID);
                    if (el) el.textContent = homeVal + ':' + awayVal;

                    // Sync row data attributes so re-opening the modal shows correct values
                    const row = document.querySelector(`.upcoming-row[data-game-id="${this.gameID}"]`);
                    if (row) {
                        row.dataset.hscore = homeVal;
                        row.dataset.ascore = awayVal;
                        row.dataset.penaltyWinner = (isDraw && this.penaltyWinner) ? this.penaltyWinner : '';

                        // Refresh penalty winner badge
                        const homeSpan = row.querySelector('.upcoming-home');
                        const awaySpan = row.querySelector('.upcoming-away');
                        if (homeSpan) homeSpan.querySelectorAll('.upcoming-pw-badge').forEach(b => b.remove());
                        if (awaySpan) awaySpan.querySelectorAll('.upcoming-pw-badge').forEach(b => b.remove());

                        if (isDraw && this.penaltyWinner) {
                            const badge = document.createElement('i');
                            badge.className = 'bi bi-check-circle-fill upcoming-pw-badge';
                            if (this.penaltyWinner === this.homeId && homeSpan) {
                                homeSpan.appendChild(badge);
                            } else if (this.penaltyWinner === this.awayId && awaySpan) {
                                awaySpan.insertBefore(badge, awaySpan.firstChild);
                            }
                        }

                        // Update odds in row data attributes and live display
                        if (data.home_odds !== undefined) {
                            row.dataset.homeOdds = data.home_odds;
                            row.dataset.drawOdds = data.draw_odds;
                            row.dataset.awayOdds = data.away_odds;
                            this._updateOddsDisplay(data.home_odds, data.draw_odds, data.away_odds);
                        }
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
