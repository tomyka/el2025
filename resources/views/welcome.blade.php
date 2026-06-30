<div class="sb-charity-card mt-3">
    <div class="sb-charity-card-inner">
        <div class="sb-charity-icon">♥</div>
        <div class="sb-charity-body">
            <h3 class="sb-charity-title">Žaidžiame dėl gero tikslo</h3>
            <p class="sb-charity-text">
                SportBet — tai ne tik futbolo prognozių žaidimas. Nuo 2018 metų žaidėjai savanoriškai
                aukoja <a href="{{ route('charity') }}" class="sb-charity-link-inline">Jaunimo linijai</a>,
                teikiančiai psichologinę pagalbą jaunimui visoje Lietuvoje.
                Iki 2024 m. kiekvieną auką dvigubino TransUnion Lithuania.
            </p>
            <a href="{{ route('charity') }}" class="sb-charity-link">
                Sužinoti daugiau <i class="bi bi-arrow-right-short"></i>
            </a>
        </div>
        <div class="sb-charity-stat-box">
            <span class="sb-charity-amount">7 500€</span>
            <span class="sb-charity-stat-label">paaukota nuo 2018 m.</span>
        </div>
    </div>
</div>

@php
    $medalRows = collect(\Illuminate\Support\Facades\DB::select('
        SELECT ps.final AS position, t.team, COUNT(*) AS votes
        FROM prediction_standings ps
        JOIN teams t ON t.id = ps.team_id
        JOIN user_settings us ON us.user_id = ps.user_id
        WHERE ps.final IN (1,2,3) AND us.active = 1
        GROUP BY ps.final, t.id, t.team
        ORDER BY ps.final ASC, votes DESC
    '))->groupBy('position')->map(fn($rows) => $rows->first());
    $medalEmojis = [1 => '🥇', 2 => '🥈', 3 => '🥉'];
    $medalLabels = [1 => 'Čempionas', 2 => 'Antra vieta', 3 => 'Trečia vieta'];
@endphp
<div class="row g-3 mt-0">

    {{-- Leaderboard --}}
    <div class="col-md-6">
        <div class="sb-card h-100">
            <div class="sb-card-title">
                <i class="bi bi-trophy-fill sb-card-icon" style="color:#f59e0b"></i> Lyderiai
                <a href="{{ route('leaderboard') }}" class="upcoming-all-link ms-auto">Visos vietos <i class="bi bi-arrow-right-short"></i></a>
            </div>
            @php
                $topPlayers = \Illuminate\Support\Facades\DB::select('
                    SELECT u.username,
                           ROUND(SUM(IFNULL(pr.full_points,0) + IFNULL(pr.streak_bonus,0)),1) AS total_points
                    FROM users u
                    JOIN user_settings us ON us.user_id = u.id
                    JOIN point_results pr ON pr.user_id = u.id
                    WHERE us.active = 1
                    GROUP BY u.id, u.username
                    HAVING total_points > 0
                    ORDER BY total_points DESC
                    LIMIT 5
                ');
            @endphp
            @if(count($topPlayers))
            <div style="display:flex;flex-direction:column;gap:6px;">
                @foreach($topPlayers as $i => $p)
                <div style="display:flex;align-items:center;gap:10px;padding:6px 0;{{ $i < count($topPlayers)-1 ? 'border-bottom:1px solid var(--sb-border)' : '' }}">
                    <span style="font-size:1.1rem;width:28px;text-align:center;flex-shrink:0">
                        @if($i===0)🥇@elseif($i===1)🥈@elseif($i===2)🥉@else<span style="color:var(--sb-muted);font-size:.85rem">{{ $i+1 }}</span>@endif
                    </span>
                    <span style="flex:1;font-weight:600;font-size:.9rem">{{ $p->username }}</span>
                    <span style="font-weight:700;color:var(--sb-accent);font-size:.9rem">{{ number_format($p->total_points, 1) }} pt</span>
                </div>
                @endforeach
            </div>
            @endif
            <div style="margin-top:12px;font-size:.8rem;color:var(--sb-muted)">
                Nori patekti į lyderių lentelę?
                <a href="{{ route('login') }}" style="color:var(--sb-accent);font-weight:600">Prisijunk</a> ir pradėk prognozuoti.
            </div>
        </div>
    </div>

    {{-- Medal predictions --}}
    @if($medalRows->isNotEmpty())
    <div class="col-md-6">
        <div class="sb-card h-100">
            <div class="sb-card-title">
                <i class="bi bi-award-fill sb-card-icon" style="color:#f59e0b"></i> Medalininkai
            </div>
            <p style="font-size:.82rem;color:var(--sb-muted);margin-bottom:12px">Dažniausiai prognozuojamos prizinės vietos</p>
            <div style="display:flex;flex-direction:column;gap:6px;">
                @foreach([1,2,3] as $pos)
                @if($medalRows->has($pos))
                @php $m = $medalRows[$pos]; @endphp
                <div style="display:flex;align-items:center;gap:10px;padding:8px 0;{{ $pos < 3 ? 'border-bottom:1px solid var(--sb-border)' : '' }}">
                    <span style="font-size:1.25rem;width:32px;text-align:center;flex-shrink:0">{{ $medalEmojis[$pos] }}</span>
                    <img src="{{ asset('img/teams/'.str_replace(' ','%20',strtolower($m->team)).'.svg') }}"
                         style="width:24px;height:24px;border-radius:50%;object-fit:cover;flex-shrink:0"
                         alt="{{ $m->team }}">
                    <span style="flex:1;font-weight:600;font-size:.9rem">{{ $m->team }}</span>
                    <span style="font-size:.78rem;color:var(--sb-muted);white-space:nowrap">{{ $m->votes }} {{ $m->votes == 1 ? 'prognozė' : 'prognozės' }}</span>
                </div>
                @endif
                @endforeach
            </div>
            <div style="margin-top:10px;font-size:.8rem;color:var(--sb-muted)">
                Sutinki? <a href="{{ route('login') }}" style="color:var(--sb-accent);font-weight:600">Prisijunk ir pateik savo prognozę</a>.
            </div>
        </div>
    </div>
    @endif

</div>

<p style="font-size:.8rem;opacity:.65;margin:16px 0 0;border-top:1px solid rgba(255,255,255,.15);padding-top:12px;color:var(--sb-muted)">
    SportBet yra nemokamas pramoginis žaidimas — realių pinigų lažybų nėra. Žaidžiame dėl taškų ir tradicijos.
</p>
