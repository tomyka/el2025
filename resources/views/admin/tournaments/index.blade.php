@extends('admin.layouts.master')
@section('content')

<div class="sb-card-title mb-3">
  <i class="bi bi-globe2 sb-card-icon"></i> Turnyrai
  <a href="{{ route('admin.tournaments.create') }}" class="sb-btn sb-btn-primary ms-auto" style="font-size:.8rem">
    + Naujas turnyras
  </a>
</div>

@if(session('info'))
  <div class="alert alert-info">{{ session('info') }}</div>
@endif

<div class="table-responsive">
<table class="table">
  <thead>
    <tr>
      <th>#</th><th>Pavadinimas</th><th>Sportas</th><th>Statusas</th><th>Pradžia</th><th>Veiksmai</th>
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
      <td class="d-flex gap-1">
        <a href="{{ route('admin.tournaments.edit', $t) }}" class="sb-btn sb-btn-secondary" style="font-size:.75rem">Redaguoti</a>
        <form method="POST" action="{{ route('admin.tournaments.context', $t) }}">
          @csrf
          <button class="sb-btn sb-btn-primary" style="font-size:.75rem" title="Dirbti šiame turnyre">▶ Kontekstas</button>
        </form>
      </td>
    </tr>
    @endforeach
  </tbody>
</table>
</div>

@endsection
