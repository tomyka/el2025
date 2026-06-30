@extends('layouts.master')
@section('content')

{{-- Charity card on top --}}
<div class="sb-charity-card mb-3">
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

{{-- Tournament list --}}
<div class="sb-card mb-3">
  <div class="sb-card-title">
    <i class="bi bi-globe2 sb-card-icon"></i> Turnyrai
  </div>
  <p style="font-size:.9rem;color:var(--sb-muted);margin:0 0 16px">
    Pasirinkite turnyrą, kuriame norite dalyvauti.
  </p>

  @foreach([['active','🔴 Vykstantys'],['upcoming','⏳ Artėjantys'],['finished','📁 Pasibaigę']] as [$status, $label])
  @php $group = $tournaments->where('status', $status); @endphp
  @if($group->isNotEmpty())
  <div style="font-weight:700;font-size:.88rem;margin-bottom:10px;margin-top:18px;">{{ $label }}</div>
  <div class="row g-3 mb-2">
    @foreach($group as $t)
    @php
      $membership  = $myLeaguesByTournament->get($t->id);
      $hasLeague   = !is_null($membership);
      $participantCount = \App\Models\LeagueMember::whereHas('league', fn($q)=>$q->where('tournament_id',$t->id))
                          ->where('is_guest',false)->distinct()->count('user_id');
    @endphp
    <div class="col-md-4 col-sm-6">
      {{-- For guests: whole card links to tournament details --}}
      @guest
      <a href="{{ route('tournament.show', $t->slug) }}" style="text-decoration:none;color:inherit;display:block;height:100%">
      @endguest
      <div class="sb-card h-100" style="{{ $status==='active' ? 'border:2px solid var(--sb-accent)' : '' }}{{ Auth::guest() ? ';cursor:pointer' : '' }}">
        <div style="font-weight:700;font-size:1rem;margin-bottom:4px">{{ $t->name }}</div>
        <div style="font-size:.78rem;color:var(--sb-muted);margin-bottom:8px">
          {{ ucfirst($t->sport) }}
          @if($t->start_date) · {{ $t->start_date->format('Y') }} @endif
          · {{ $participantCount }} dalyviai
        </div>
        @if($hasLeague)
        <span style="font-size:.72rem;background:var(--sb-accent);color:#fff;border-radius:4px;padding:2px 8px;display:inline-block;margin-bottom:10px">
          {{ $membership->league->name }}
        </span>
        @endif
        @if($t->description)
        <p style="font-size:.82rem;color:var(--sb-muted);margin-bottom:10px">{{ $t->description }}</p>
        @endif

        @auth
          @if($status === 'active' || $status === 'upcoming')
            <form method="POST" action="{{ route('tournament.enter', $t->slug) }}">
              @csrf
              <button class="sb-btn sb-btn-primary w-100">
                {{ $hasLeague ? 'Žaisti →' : 'Peržiūrėti / Prisijungti →' }}
              </button>
            </form>
          @else
            <a href="{{ route('tournament.show', $t->slug) }}" class="sb-btn sb-btn-secondary w-100">
              Peržiūrėti rezultatus →
            </a>
          @endif
        @endauth
      </div>
      @guest
      </a>
      @endguest
    </div>
    @endforeach
  </div>
  @endif
  @endforeach

</div>

{{-- Leaderboard + Medal predictions --}}
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

@if(count($topPlayers) || count($medalRows))
<div class="row g-3 mb-3">

  @if(count($topPlayers))
  <div class="col-md-6">
    <div class="sb-card h-100">
      <div class="sb-card-title">
        <i class="bi bi-trophy-fill sb-card-icon" style="color:#f59e0b"></i> Lyderiai
        <a href="{{ route('leaderboard') }}" class="upcoming-all-link ms-auto">Visos vietos <i class="bi bi-arrow-right-short"></i></a>
      </div>
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
      <div style="margin-top:12px;font-size:.8rem;color:var(--sb-muted)">
        Nori patekti į lyderių lentelę?
        <a href="{{ route('login') }}" style="color:var(--sb-accent);font-weight:600">Prisijunk</a> ir pradėk prognozuoti.
      </div>
    </div>
  </div>
  @endif

  @if(count($medalRows))
  <div class="col-md-6">
    <div class="sb-card h-100">
      <div class="sb-card-title"><i class="bi bi-graph-up-arrow sb-card-icon"></i> Finalų dalyvių prognozės</div>
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
    </div>
  </div>
  @endif

</div>
@endif

<p style="font-size:.78rem;opacity:.65;margin-top:8px;color:var(--sb-muted)">
  SportBet yra nemokamas pramoginis žaidimas — realių pinigų lažybų nėra.
</p>

@guest
@include('modals.main')
@endguest

@endsection
