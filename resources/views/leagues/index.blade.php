@extends('layouts.master')

@section('content')
<div class="container py-4">

  @if(session('info'))
    <div class="alert alert-info alert-dismissible fade show" role="alert">
      {{ session('info') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  {{-- Invite Inbox --}}
  @if($pendingInvites->count())
  <div class="mb-4">
    <h5 class="fw-bold mb-3">Kvietimai <span class="badge bg-danger">{{ $pendingInvites->count() }}</span></h5>
    @foreach($pendingInvites as $invite)
    <div class="card mb-2">
      <div class="card-body d-flex align-items-center justify-content-between py-2">
        <div>
          <strong>{{ $invite->league->name }}</strong>
          <span class="text-muted small ms-2">pakvietė {{ $invite->invitedBy->username ?? '—' }}</span>
        </div>
        <div class="d-flex gap-2">
          <form method="POST" action="{{ route('leagues.accept') }}">
            @csrf
            <input type="hidden" name="inviteID" value="{{ $invite->id }}">
            <button type="submit" class="btn btn-success btn-sm">Priimti</button>
          </form>
          <form method="POST" action="{{ route('leagues.decline') }}">
            @csrf
            <input type="hidden" name="inviteID" value="{{ $invite->id }}">
            <button type="submit" class="btn btn-outline-secondary btn-sm">Atmesti</button>
          </form>
        </div>
      </div>
    </div>
    @endforeach
  </div>
  @endif

  {{-- My Leagues --}}
  <h5 class="fw-bold mb-3">Mano lygos</h5>
  <div class="row g-3 mb-4">
    @foreach($myLeagues as $membership)
    @php $league = $membership->league; @endphp
    <div class="col-md-6">
      <div class="card h-100 {{ $membership->active ? 'border-primary' : '' }}">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div>
              <h6 class="mb-1 fw-bold">
                {{ $league->name }}
                @if($membership->active)
                  <span class="badge bg-primary ms-1" style="font-size:.65rem;">AKTYVI</span>
                @endif
                @if($league->is_public)
                  <span class="badge bg-secondary ms-1" style="font-size:.65rem;">VIEŠA</span>
                @endif
              </h6>
              <div class="text-muted small">
                {{ $league->members()->count() }} nariai
                @if($league->base_fee)
                  · Įmoka: {{ $league->base_fee }}€
                  @if($league->penalty_step)
                    + {{ $league->penalty_step }}€/vieta
                  @endif
                @endif
              </div>
              @if($league->description)
                <div class="text-muted small mt-1">{{ $league->description }}</div>
              @endif
            </div>
          </div>

          <div class="mt-3 d-flex gap-2 flex-wrap">
            @if(!$membership->active)
            <form method="POST" action="{{ route('leagues.switch') }}">
              @csrf
              <input type="hidden" name="leagueID" value="{{ $league->id }}">
              <button type="submit" class="btn btn-outline-primary btn-sm">Perjungti</button>
            </form>
            @endif

            @if($membership->is_admin)
            <button type="button" class="btn btn-outline-secondary btn-sm"
                    onclick="openManageModal({{ $league->id }}, {{ json_encode($league->name) }})">
              Valdyti
            </button>
            @endif

            @if(!$league->is_public)
              @if($league->owner_id !== session('userID'))
              <form method="POST" action="{{ route('leagues.leave') }}"
                    onsubmit="return confirm('Palikti lygą {{ addslashes($league->name) }}?')">
                @csrf
                <input type="hidden" name="leagueID" value="{{ $league->id }}">
                <button type="submit" class="btn btn-outline-danger btn-sm">Palikti</button>
              </form>
              @else
              <span class="text-muted small align-self-center">Savininkas</span>
              @endif
            @endif
          </div>
        </div>
      </div>
    </div>
    @endforeach
  </div>

  {{-- Create League --}}
  <h5 class="fw-bold mb-3">Sukurti naują lygą</h5>
  <div class="card">
    <div class="card-body">
      <form method="POST" action="{{ route('leagues.create') }}">
        @csrf
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label small">Pavadinimas *</label>
            <input type="text" name="name" class="form-control form-control-sm" required maxlength="100">
          </div>
          <div class="col-md-6">
            <label class="form-label small">Aprašymas</label>
            <input type="text" name="description" class="form-control form-control-sm" maxlength="255">
          </div>
          <div class="col-md-3">
            <label class="form-label small">Bazinė įmoka (€)</label>
            <input type="number" name="base_fee" class="form-control form-control-sm" min="0">
          </div>
          <div class="col-md-3">
            <label class="form-label small">Bauda už vietą (€)</label>
            <input type="number" name="penalty_step" class="form-control form-control-sm" min="0">
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-primary btn-sm">Sukurti lygą</button>
          </div>
        </div>
      </form>
    </div>
  </div>

</div>

{{-- Manage League Modal --}}
<div class="modal fade" id="manageModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="manageModalTitle">Valdyti lygą</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <h6>Pakviesti narį</h6>
        <div class="input-group mb-2">
          <input type="text" id="inviteSearch" class="form-control form-control-sm"
                 placeholder="Ieškoti pagal vardą arba vartotojo vardą..."
                 oninput="searchUsers(this.value)">
        </div>
        <div id="searchResults" class="list-group mb-3"></div>

        <form id="inviteForm" method="POST" action="{{ route('leagues.invite') }}">
          @csrf
          <input type="hidden" id="inviteLeagueID" name="leagueID">
          <input type="hidden" id="invitedUserID" name="invitedUserID">
        </form>
      </div>
    </div>
  </div>
</div>

<script>
let activeManageLeagueId = null;

function openManageModal(leagueId, leagueName) {
    activeManageLeagueId = leagueId;
    document.getElementById('manageModalTitle').textContent = 'Valdyti: ' + leagueName;
    document.getElementById('inviteLeagueID').value = leagueId;
    document.getElementById('searchResults').innerHTML = '';
    document.getElementById('inviteSearch').value = '';
    new bootstrap.Modal(document.getElementById('manageModal')).show();
}

function searchUsers(query) {
    if (query.length < 2) {
        document.getElementById('searchResults').innerHTML = '';
        return;
    }
    fetch(`{{ route('leagues.searchUsers') }}?query=${encodeURIComponent(query)}&leagueID=${activeManageLeagueId}`)
        .then(r => r.json())
        .then(users => {
            const container = document.getElementById('searchResults');
            if (users.length === 0) {
                container.innerHTML = '<div class="list-group-item list-group-item-action text-muted small">Nerasta</div>';
                return;
            }
            container.innerHTML = users.map(u =>
                `<button type="button" class="list-group-item list-group-item-action small"
                         onclick="selectInvitee(${u.id})">
                   ${u.name} ${u.surname} <span class="text-muted">(${u.username})</span>
                 </button>`
            ).join('');
        });
}

function selectInvitee(userId) {
    document.getElementById('invitedUserID').value = userId;
    document.getElementById('inviteForm').submit();
}
</script>
@endsection
