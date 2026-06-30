@extends('admin.layouts.master')
@section('content')

<div class="sb-card-title mb-3">
  <i class="bi bi-globe2 sb-card-icon"></i>
  {{ $tournament->exists ? 'Redaguoti turnyrą' : 'Naujas turnyras' }}
</div>

@if($errors->any())
  <div class="alert alert-danger">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
  </div>
@endif

@php
  $action = $tournament->exists
    ? route('admin.tournaments.update', $tournament)
    : route('admin.tournaments.store');
@endphp

<form method="POST" action="{{ $action }}" class="sb-card">
  @csrf
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Pavadinimas</label>
      <input class="form-control" name="name" value="{{ old('name', $tournament->name) }}" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Slug (URL)</label>
      <input class="form-control" name="slug" value="{{ old('slug', $tournament->slug) }}"
             placeholder="world-cup-2026" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Sportas</label>
      <input class="form-control" name="sport" value="{{ old('sport', $tournament->sport ?? 'football') }}" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Statusas</label>
      <select class="form-select" name="status">
        @foreach(['upcoming','active','finished'] as $s)
        <option value="{{ $s }}" {{ old('status', $tournament->status) === $s ? 'selected' : '' }}>{{ $s }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Pradžia</label>
      <input class="form-control" type="date" name="start_date"
             value="{{ old('start_date', $tournament->start_date?->format('Y-m-d')) }}">
    </div>
    <div class="col-md-3">
      <label class="form-label">Pabaiga</label>
      <input class="form-control" type="date" name="end_date"
             value="{{ old('end_date', $tournament->end_date?->format('Y-m-d')) }}">
    </div>
    <div class="col-md-3">
      <label class="form-label">Viršelio paveikslėlis (kelias)</label>
      <input class="form-control" name="cover_image" value="{{ old('cover_image', $tournament->cover_image) }}">
    </div>
    <div class="col-12">
      <label class="form-label">Aprašymas</label>
      <textarea class="form-control" name="description" rows="3">{{ old('description', $tournament->description) }}</textarea>
    </div>
    <div class="col-md-3 d-flex align-items-center gap-3 pt-2">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_public" value="1"
               {{ old('is_public', $tournament->is_public ?? true) ? 'checked' : '' }}>
        <label class="form-check-label">Viešas</label>
      </div>
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="survival_game" value="1"
               {{ old('survival_game', $tournament->survival_game ?? false) ? 'checked' : '' }}>
        <label class="form-check-label">Išlikimas</label>
      </div>
    </div>
    <div class="col-12">
      <button type="submit" class="sb-btn sb-btn-primary">
        {{ $tournament->exists ? 'Atnaujinti' : 'Sukurti' }}
      </button>
      <a href="{{ route('admin.tournaments') }}" class="sb-btn sb-btn-secondary ms-2">Atšaukti</a>
    </div>
  </div>
</form>

@endsection
