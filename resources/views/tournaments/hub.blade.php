@extends('layouts.master')
@section('content')

{{-- Charity card --}}
<div class="sb-charity-card mb-4">
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

@foreach([['active','🔴 Vykstantys turnyrai'],['upcoming','⏳ Artėjantys turnyrai'],['finished','📁 Pasibaigę turnyrai']] as [$status, $groupLabel])
@php $group = $tournaments->where('status', $status); @endphp
@if($group->isNotEmpty())

<div style="font-weight:700;font-size:.82rem;letter-spacing:.08em;text-transform:uppercase;color:var(--sb-muted);margin:28px 0 12px">
  {{ $groupLabel }}
</div>

@foreach($group as $t)
@php
  $d        = $tData[$t->id];
  $membership = $myLeaguesByTournament->get($t->id);
  $inLeague   = !is_null($membership);
@endphp

<div class="sb-card mb-4">

  {{-- Tournament header --}}
  <div style="display:flex;align-items:flex-start;gap:12px;flex-wrap:wrap;margin-bottom:20px">
    <div style="flex:1;min-width:0">
      <div style="font-weight:700;font-size:1.15rem;margin-bottom:4px">{{ $t->name }}</div>
      <div style="font-size:.8rem;color:var(--sb-muted)">
        {{ ucfirst($t->sport) }}
        @if($t->start_date && $t->end_date)
          · {{ $t->start_date->format('Y-m-d') }} – {{ $t->end_date->format('Y-m-d') }}
        @elseif($t->start_date)
          · nuo {{ $t->start_date->format('Y-m-d') }}
        @endif
        · <strong>{{ $d['participantCount'] }}</strong> dalyviai
        @if($d['predictionCount'] > 0)
          · <strong>{{ number_format($d['predictionCount']) }}</strong> prognozės
        @endif
      </div>
      @if($t->description)
      <p style="font-size:.85rem;color:var(--sb-muted);margin:8px 0 0">{{ $t->description }}</p>
      @endif
    </div>

    {{-- Action button --}}
    <div style="flex-shrink:0">
      @if($status === 'active')
        @auth
          @if($inLeague)
          <form method="POST" action="{{ route('tournament.enter', $t->slug) }}">
            @csrf
            <button class="sb-btn sb-btn-primary">Žaisti →</button>
          </form>
          @endif
        @endauth
      @elseif($status === 'upcoming')
        @auth
          <form method="POST" action="{{ route('tournament.enter', $t->slug) }}">
            @csrf
            <button class="sb-btn sb-btn-primary">Prisijungti anksti →</button>
          </form>
        @else
          <a href="{{ route('login') }}" class="sb-btn sb-btn-primary">Registruotis →</a>
        @endauth
      @else
        <a href="{{ route('tournament.show', $t->slug) }}" class="sb-btn sb-btn-secondary">Peržiūrėti rezultatus →</a>
      @endif
    </div>
  </div>

  {{-- Widgets --}}
  @if($status === 'upcoming')

    {{-- Upcoming: how it works + schedule if any --}}
    <div class="row g-3">
      <div class="{{ count($d['upcomingGames']) ? 'col-md-7' : 'col-12' }}">
        <div style="background:var(--sb-surface-2,var(--sb-surface));border:1px solid var(--sb-border);border-radius:10px;padding:18px">
          <div style="font-weight:700;font-size:.88rem;margin-bottom:14px"><i class="bi bi-info-circle sb-card-icon"></i> Kaip tai veikia?</div>
          <div style="display:flex;flex-direction:column;gap:12px">
            <div style="display:flex;gap:12px;align-items:flex-start">
              <span style="font-size:1.4rem;line-height:1">🎯</span>
              <div>
                <div style="font-weight:600;font-size:.88rem">Spėk rungtynių rezultatus</div>
                <div style="font-size:.8rem;color:var(--sb-muted)">Prognozuok tikslų rezultatą prieš kiekvieną rungtynę</div>
              </div>
            </div>
            <div style="display:flex;gap:12px;align-items:flex-start">
              <span style="font-size:1.4rem;line-height:1">📊</span>
              <div>
                <div style="font-weight:600;font-size:.88rem">Rink taškus</div>
                <div style="font-size:.8rem;color:var(--sb-muted)">Taškus gauni už tikslų rezultatą, nugalėtoją ir įvarčių skirtumą</div>
              </div>
            </div>
            <div style="display:flex;gap:12px;align-items:flex-start">
              <span style="font-size:1.4rem;line-height:1">🏆</span>
              <div>
                <div style="font-weight:600;font-size:.88rem">Konkuruok lygoje</div>
                <div style="font-size:.8rem;color:var(--sb-muted)">Sukurk privačią lygą su draugais arba prisijunk prie esamos</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      @if(count($d['upcomingGames']))
      <div class="col-md-5">
        <div style="background:var(--sb-surface-2,var(--sb-surface));border:1px solid var(--sb-border);border-radius:10px;padding:18px;height:100%">
          <div style="font-weight:700;font-size:.88rem;margin-bottom:14px"><i class="bi bi-calendar3 sb-card-icon"></i> Artėjančios rungtynės</div>
          <div style="display:flex;flex-direction:column;gap:10px">
            @foreach($d['upcomingGames'] as $game)
            <div style="font-size:.85rem">
              <div style="color:var(--sb-muted);font-size:.75rem;margin-bottom:2px">
                {{ \Carbon\Carbon::parse($game->game_date)->format('M d, H:i') }}
              </div>
              <div style="font-weight:600">{{ $game->home_team }} <span style="color:var(--sb-muted);font-weight:400">vs</span> {{ $game->away_team }}</div>
            </div>
            @endforeach
          </div>
        </div>
      </div>
      @endif
    </div>

  @else

    {{-- Active / Finished: leaderboard, medals, upcoming games, stats --}}
    <div class="row g-3">

      {{-- Leaderboard --}}
      @if(count($d['leaderboard']))
      <div class="col-md-4">
        <div style="background:var(--sb-surface-2,var(--sb-surface));border:1px solid var(--sb-border);border-radius:10px;padding:18px;height:100%">
          <div style="font-weight:700;font-size:.88rem;margin-bottom:14px">
            <i class="bi bi-trophy-fill sb-card-icon" style="color:#f59e0b"></i> Lyderiai
            <a href="{{ route('leaderboard') }}" class="upcoming-all-link ms-auto" style="font-size:.75rem">Visos vietos →</a>
          </div>
          <div style="display:flex;flex-direction:column;gap:6px">
            @foreach($d['leaderboard'] as $i => $p)
            <div style="display:flex;align-items:center;gap:8px;padding:5px 0;{{ $i < count($d['leaderboard'])-1 ? 'border-bottom:1px solid var(--sb-border)' : '' }}">
              <span style="font-size:1rem;width:24px;text-align:center;flex-shrink:0">
                @if($i===0)🥇@elseif($i===1)🥈@elseif($i===2)🥉@else<span style="color:var(--sb-muted);font-size:.8rem">{{ $i+1 }}</span>@endif
              </span>
              <span style="flex:1;font-weight:600;font-size:.85rem">{{ $p->username }}</span>
              <span style="font-weight:700;color:var(--sb-accent);font-size:.85rem">{{ number_format($p->total_points, 1) }} pt</span>
            </div>
            @endforeach
          </div>
        </div>
      </div>
      @endif

      {{-- Medal predictions --}}
      @if(count($d['medalRows']))
      <div class="col-md-4">
        <div style="background:var(--sb-surface-2,var(--sb-surface));border:1px solid var(--sb-border);border-radius:10px;padding:18px;height:100%">
          <div style="font-weight:700;font-size:.88rem;margin-bottom:14px"><i class="bi bi-graph-up-arrow sb-card-icon"></i> Finalų prognozės</div>
          <div class="stnl-list">
            @foreach($d['medalRows'] as $standing)
            <div class="stnl-row">
              <span class="stnl-team">
                <img class="standing-flag"
                     src="{{ asset('img/teams/' . str_replace(' ', '%20', strtolower($standing->team)) . '.svg') }}"
                     alt="{{ $standing->team }}">
                <span class="stnl-name" style="font-size:.82rem">{{ $standing->team }}</span>
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

      {{-- Upcoming games (active only) --}}
      @if($status === 'active' && count($d['upcomingGames']))
      <div class="{{ count($d['leaderboard']) && count($d['medalRows']) ? 'col-md-4' : 'col-md-6' }}">
        <div style="background:var(--sb-surface-2,var(--sb-surface));border:1px solid var(--sb-border);border-radius:10px;padding:18px;height:100%">
          <div style="font-weight:700;font-size:.88rem;margin-bottom:14px"><i class="bi bi-calendar3 sb-card-icon"></i> Artėjančios rungtynės</div>
          <div style="display:flex;flex-direction:column;gap:10px">
            @foreach($d['upcomingGames'] as $game)
            <div style="font-size:.85rem">
              <div style="color:var(--sb-muted);font-size:.75rem;margin-bottom:2px">
                {{ \Carbon\Carbon::parse($game->game_date)->format('M d, H:i') }}
              </div>
              <div style="font-weight:600">{{ $game->home_team }} <span style="color:var(--sb-muted);font-weight:400">vs</span> {{ $game->away_team }}</div>
            </div>
            @endforeach
          </div>
        </div>
      </div>
      @endif

      {{-- Stats --}}
      <div class="{{ count($d['leaderboard']) && count($d['medalRows']) && ($status === 'active' && count($d['upcomingGames'])) ? 'col-12' : 'col-md-4' }}">
        <div style="background:var(--sb-surface-2,var(--sb-surface));border:1px solid var(--sb-border);border-radius:10px;padding:18px;height:100%">
          <div style="font-weight:700;font-size:.88rem;margin-bottom:14px"><i class="bi bi-bar-chart-fill sb-card-icon"></i> Statistika</div>
          <div style="display:flex;flex-wrap:wrap;gap:16px">
            <div>
              <div style="font-size:1.4rem;font-weight:700;color:var(--sb-accent)">{{ $d['participantCount'] }}</div>
              <div style="font-size:.75rem;color:var(--sb-muted)">dalyviai</div>
            </div>
            @if($d['predictionCount'] > 0)
            <div>
              <div style="font-size:1.4rem;font-weight:700;color:var(--sb-accent)">{{ number_format($d['predictionCount']) }}</div>
              <div style="font-size:.75rem;color:var(--sb-muted)">prognozės</div>
            </div>
            @endif
          </div>
        </div>
      </div>

    </div>{{-- end widgets row --}}
  @endif

</div>{{-- end tournament card --}}
@endforeach
@endif
@endforeach

<p style="font-size:.78rem;opacity:.65;margin-top:8px;color:var(--sb-muted)">
  SportBet yra nemokamas pramoginis žaidimas — realių pinigų lažybų nėra.
</p>

@guest
@include('modals.main')
@endguest

@endsection
