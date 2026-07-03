<div class="sb-charity-card mt-3">
    <div class="sb-charity-card-inner">
        <div class="sb-charity-icon">♥</div>
        <div class="sb-charity-body">
            <h3 class="sb-charity-title">{{ __('Žaidžiame dėl gero tikslo') }}</h3>
            <p class="sb-charity-text">
                {{ __('SportBet — tai ne tik futbolo prognozių žaidimas. Nuo 2018 metų žaidėjai savanoriškai aukoja') }}
                <a href="{{ route('charity') }}" class="sb-charity-link-inline">{{ __('Jaunimo linijai') }}</a>,
                {{ __('teikiančiai psichologinę pagalbą jaunimui visoje Lietuvoje. Iki 2024 m. kiekvieną auką dvigubino TransUnion Lithuania.') }}
            </p>
            <a href="{{ route('charity') }}" class="sb-charity-link">
                {{ __('Sužinoti daugiau') }} <i class="bi bi-arrow-right-short"></i>
            </a>
        </div>
        <div class="sb-charity-stat-box">
            <span class="sb-charity-amount">7 500€</span>
            <span class="sb-charity-stat-label">{{ __('paaukota nuo 2018 m.') }}</span>
        </div>
    </div>
</div>

@php
    $medalRows = \Illuminate\Support\Facades\DB::select('
        SELECT t.team,
            SUM(CASE WHEN ps.final = 1 THEN 1 ELSE 0 END) AS firstPlacePrediction,
            SUM(CASE WHEN ps.final = 2 THEN 1 ELSE 0 END) AS secondPlacePrediction,
            SUM(CASE WHEN ps.final = 3 THEN 1 ELSE 0 END) AS thirdPlacePrediction,
            SUM(CASE WHEN ps.final = 4 THEN 1 ELSE 0 END) AS fourthPlacePrediction
        FROM prediction_standings ps
        JOIN teams t ON ps.team_id = t.id
        JOIN user_settings us ON ps.user_id = us.user_id
        WHERE ps.final IS NOT NULL AND us.active = 1
        GROUP BY t.team
        ORDER BY firstPlacePrediction DESC, secondPlacePrediction DESC, thirdPlacePrediction DESC
    ');
@endphp
<div class="row g-3 mt-0">

    {{-- Leaderboard --}}
    <div class="col-md-6">
        <div class="sb-card h-100">
            <div class="sb-card-title">
                <i class="bi bi-trophy-fill sb-card-icon" style="color:#f59e0b"></i> {{ __('Lyderiai') }}
                <a href="{{ route('leaderboard') }}" class="upcoming-all-link ms-auto">{{ __('Visos vietos') }} <i class="bi bi-arrow-right-short"></i></a>
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
                {{ __('Nori patekti į lyderių lentelę?') }}
                <a href="{{ route('login') }}" style="color:var(--sb-accent);font-weight:600">{{ __('Prisijunk') }}</a> {{ __('ir pradėk prognozuoti.') }}
            </div>
        </div>
    </div>

    {{-- Medal predictions --}}
    @if(count($medalRows))
    <div class="col-md-6">
        <div class="sb-card h-100">
            <div class="sb-card-title"><i class="bi bi-graph-up-arrow sb-card-icon"></i> {{ __('Finalų dalyvių prognozės') }}</div>
            <div class="stnl-list">
                @foreach($medalRows as $standing)
                <div class="stnl-row">
                    <span class="stnl-team">
                        <img class="standing-flag"
                             src="{{ asset('img/teams/' . str_replace(' ', '%20', strtolower($standing->team)) . '.svg') }}"
                             alt="{{ $standing->team }}">
                        <span class="stnl-name">{{ $standing->team }}</span>
                    </span>
                    <span class="stnl-preds">
                        <span class="standing-pos-badge pos-1 {{ $standing->firstPlacePrediction  == 0 ? 'pos-zero' : '' }}">{{ $standing->firstPlacePrediction }}</span>
                        <span class="standing-pos-badge pos-2 {{ $standing->secondPlacePrediction == 0 ? 'pos-zero' : '' }}">{{ $standing->secondPlacePrediction }}</span>
                        <span class="standing-pos-badge pos-3 {{ $standing->thirdPlacePrediction  == 0 ? 'pos-zero' : '' }}">{{ $standing->thirdPlacePrediction }}</span>
                        <span class="standing-pos-badge pos-4 {{ $standing->fourthPlacePrediction == 0 ? 'pos-zero' : '' }}">{{ $standing->fourthPlacePrediction }}</span>
                    </span>
                </div>
                @endforeach
            </div>
            <div style="margin-top:10px;font-size:.8rem;color:var(--sb-muted)">
                <a href="{{ route('login') }}" style="color:var(--sb-accent);font-weight:600">{{ __('Prisijunk') }}</a> {{ __('ir pateik savo prognozę.') }}
            </div>
        </div>
    </div>
    @endif

</div>

<p style="font-size:.8rem;opacity:.65;margin:16px 0 0;border-top:1px solid rgba(255,255,255,.15);padding-top:12px;color:var(--sb-muted)">
    {{ __('SportBet yra nemokamas pramoginis žaidimas — realių pinigų lažybų nėra. Žaidžiame dėl taškų ir tradicijos.') }}
</p>
