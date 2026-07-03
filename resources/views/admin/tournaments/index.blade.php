@extends('admin.layouts.master')
@section('content')

<div class="sb-card">
  <div class="sb-card-title d-flex align-items-center justify-content-between">
    <span><i class="bi bi-globe2 sb-card-icon"></i> {{ __('Turnyrai') }} <span class="badge bg-secondary fw-normal ms-1">{{ $tournaments->count() }}</span></span>
    <a href="{{ route('admin.tournaments.create') }}" class="btn btn-sm btn-primary">
      <i class="bi bi-plus-lg"></i> {{ __('Naujas turnyras') }}
    </a>
  </div>

  @if(session('info'))
  <div class="alert alert-success py-2 mb-3">{{ session('info') }}</div>
  @endif

  <div class="table-responsive">
  <table class="table table-hover align-middle mb-0">
    <thead class="table-light">
      <tr>
        <th>#</th><th>{{ __('Pavadinimas') }}</th><th>{{ __('Sportas') }}</th><th>{{ __('Statusas') }}</th><th>{{ __('Pradžia') }}</th><th></th>
      </tr>
    </thead>
    <tbody>
      @foreach($tournaments as $t)
      <tr>
        <td>{{ $t->id }}</td>
        <td><strong>{{ $t->name }}</strong><br><small class="text-muted">{{ $t->slug }}</small></td>
        <td>{{ $t->sport }}</td>
        <td>
          <span class="badge {{ $t->status==='active'?'bg-success':($t->status==='upcoming'?'bg-warning':'bg-secondary') }}">
            {{ $t->status }}
          </span>
        </td>
        <td>{{ $t->start_date?->format('Y-m-d') ?? '—' }}</td>
        <td class="text-end" style="white-space:nowrap;">
          <a href="{{ route('admin.tournaments.edit', $t) }}" class="btn btn-sm btn-outline-secondary">{{ __('Redaguoti') }}</a>
          <form method="POST" action="{{ route('admin.tournaments.context', $t) }}" class="d-inline">
            @csrf
            <button class="btn btn-sm btn-primary" title="{{ __('Dirbti šiame turnyre') }}">{{ __('▶ Kontekstas') }}</button>
          </form>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
  </div>
</div>

@endsection
