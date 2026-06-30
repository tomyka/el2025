@extends('layouts.master')
@section('content')

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
      <div class="sb-card h-100" style="{{ $status==='active' ? 'border:2px solid var(--sb-accent)' : '' }}">
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
        @else
          <a href="{{ route('login') }}" class="sb-btn sb-btn-primary w-100">Prisijungti →</a>
        @endauth
      </div>
    </div>
    @endforeach
  </div>
  @endif
  @endforeach

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

<p style="font-size:.78rem;opacity:.65;margin-top:8px;color:var(--sb-muted)">
  SportBet yra nemokamas pramoginis žaidimas — realių pinigų lažybų nėra.
</p>

@guest
@include('modals.main')
@endguest

@endsection
