@extends('admin.layouts.master')
@section('content')

<div class="sb-card">
    <div class="sb-card-title d-flex align-items-center gap-2">
        <i class="bi bi-trophy-fill sb-card-icon"></i> Lygos
        <span class="badge bg-secondary fw-normal">{{ $leagues->count() }}</span>
    </div>

    @if(Session::has('info'))
    <div class="alert alert-success py-2 mb-3">{{ Session::get('info') }}</div>
    @endif
    @if(Session::has('error'))
    <div class="alert alert-danger py-2 mb-3">{{ Session::get('error') }}</div>
    @endif

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Pavadinimas</th>
                    <th class="d-none d-md-table-cell">Savininkas</th>
                    <th class="text-center" style="width:80px;">Nariai</th>
                    <th class="d-none d-sm-table-cell text-center" style="width:80px;">Tipas</th>
                    <th class="d-none d-lg-table-cell" style="width:130px;">Sukurta</th>
                    <th style="width:60px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($leagues as $league)
                <tr>
                    <td class="text-muted" style="font-size:.8rem;">{{ $league->id }}</td>

                    <td>
                        <div style="font-weight:600;font-size:.875rem;">{{ $league->name }}</div>
                        @if($league->description)
                        <div style="font-size:.72rem;color:var(--sb-muted);">{{ $league->description }}</div>
                        @endif
                    </td>

                    <td class="d-none d-md-table-cell" style="font-size:.82rem;">
                        @if($league->owner)
                            <span style="font-weight:600;">{{ $league->owner->username }}</span>
                            <span style="color:var(--sb-muted);margin-left:4px;">{{ trim($league->owner->name . ' ' . $league->owner->surname) }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>

                    <td class="text-center" style="font-size:.82rem;">{{ $league->members_count }}</td>

                    <td class="d-none d-sm-table-cell text-center">
                        @if($league->is_public)
                            <span style="font-size:.65rem;font-weight:700;padding:2px 9px;border-radius:20px;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;">Vieša</span>
                        @else
                            <span style="font-size:.65rem;font-weight:700;padding:2px 9px;border-radius:20px;background:#f1f5f9;color:var(--sb-muted);border:1px solid var(--sb-border);">Privati</span>
                        @endif
                    </td>

                    <td class="d-none d-lg-table-cell text-muted" style="font-size:.78rem;">
                        {{ $league->created_at?->format('Y-m-d') }}
                    </td>

                    <td>
                        @if(!$league->is_public && session('admin') >= 5)
                        <form method="POST" action="{{ route('admin.leagues.delete') }}"
                              onsubmit="return confirm('Ištrinti lygą \"{{ addslashes($league->name) }}\"? Visi nariai bus pašalinti.')">
                            @csrf
                            <input type="hidden" name="leagueID" value="{{ $league->id }}">
                            <button type="submit" class="btn btn-sm"
                                    style="padding:3px 10px;font-size:.75rem;color:#dc2626;border:1px solid #fca5a5;background:#fff;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4" style="font-size:.85rem;">Nėra lygų</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
