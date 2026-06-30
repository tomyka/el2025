<div class="sb-hero-banner">
    <h2>Sveiki atvykę į SportBet!</h2>
    <p>Spėk rungtynių rezultatus, prognozuok komandų eigą turnyro lentelėje ir pamatysi, kas iš tikrųjų geriausiai išmano futbolą ar krepšinį.</p>
    <p style="opacity:.8;font-size:.9rem;">Žaidžiame kartu nuo 2016 metų: Europos čempionatai, Pasaulio čempionatai, Eurobasket, Eurolygos sezonai — kiekvienas didelis turnyras tampa proga išbandyti savo žinias ir pralenkti draugus bei kolegas.</p>
    <ul style="font-size:.9rem;padding-left:18px;opacity:.9;margin:16px 0 0;display:flex;flex-direction:column;gap:6px;">
        <li>Tikra konkurencija tarp žmonių, kuriuos pažįsti</li>
        <li>Retesnis spėjimas atneša daugiau taškų bei didina intrigą.</li>
        <li>Futbolas ir krepšinis — visi didieji turnyroi vienoje vietoje.</li>
        <li>Sek savo rezultatus, lygink su kitais, džiaukis kiekviena pataikyta prognoze.</li>
    </ul>
</div>

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

<div class="sb-card mt-3">
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
            <span style="font-size:1.1rem;width:28px;text-align:center">
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

<p style="font-size:.8rem;opacity:.65;margin:16px 0 0;border-top:1px solid rgba(255,255,255,.15);padding-top:12px;color:var(--sb-muted)">
    SportBet yra nemokamas pramoginis žaidimas — realių pinigų lažybų nėra. Žaidžiame dėl taškų ir tradicijos.
</p>
