@extends('layouts.master')
@section('content')

@if($isFinished)
<div class="mb-3">
  <a href="{{ route('tournaments.hub') }}" class="sb-btn sb-btn-ghost" style="font-size:.8rem">
    ← Turnyrai
  </a>
</div>
@else
<div class="sb-card mb-3">
  <div class="sb-card-title">
    <i class="bi bi-globe2 sb-card-icon"></i> {{ $tournament->name }}
    <a href="{{ route('tournaments.hub') }}" class="ms-auto sb-btn sb-btn-ghost" style="font-size:.8rem">
      ← Turnyrai
    </a>
  </div>

  @if($tournament->description)
  <p style="color:var(--sb-muted);font-size:.9rem;margin-bottom:16px">{{ $tournament->description }}</p>
  @endif

  <div style="font-size:.85rem;color:var(--sb-muted);margin-bottom:20px">
    {{ ucfirst($tournament->sport) }}
    @if($tournament->start_date) · {{ $tournament->start_date->format('Y') }} @endif
    · {{ $participantCount }} dalyviai
  </div>

  @auth
  <div class="mb-3">
    <a href="{{ route('leagues.index') }}" class="sb-btn sb-btn-primary">
      <i class="bi bi-plus-circle"></i> Sukurti lygą šiame turnyre
    </a>
  </div>
  @else
  <a href="{{ route('login', ['tournament' => $tournament->slug]) }}" class="sb-btn sb-btn-primary">Prisijungti ir dalyvauti</a>
  @endauth
</div>
@endif

@if(!empty($points))
<div class="mb-3">
  @include('partials.points')
</div>
@endif

@if(!empty($standings))
<div class="mb-3">
  @include('partials.standings')
</div>
@endif

@endsection
