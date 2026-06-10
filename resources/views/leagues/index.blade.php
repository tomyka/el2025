@extends('layouts.master')

@section('content')

{{-- Flash messages --}}
@if(session('info'))
  <div class="alert alert-info alert-dismissible fade show mb-3" role="alert">
    <i class="bi bi-info-circle me-2"></i>{{ session('info') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif
@if(session('error'))
  <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
    <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

{{-- Invite Inbox --}}
@if($pendingInvites->count())
<div class="sb-card">
  <div class="sb-card-title">
    <i class="bi bi-envelope-fill sb-card-icon"></i>
    Kvietimai
    <span class="badge ms-1" style="background:var(--sb-accent);font-size:.62rem;vertical-align:middle;">{{ $pendingInvites->count() }}</span>
  </div>
  <div class="d-flex flex-column gap-2">
    @foreach($pendingInvites as $invite)
    <div class="d-flex align-items-center justify-content-between p-2" style="background:#f8fafc;border-radius:8px;border:1px solid var(--sb-border);">
      <div>
        <span style="font-size:.875rem;font-weight:600;">{{ $invite->league->name }}</span>
        <span style="font-size:.78rem;color:var(--sb-muted);margin-left:8px;">pakvietė {{ $invite->invitedBy->username ?? '—' }}</span>
      </div>
      <div class="d-flex gap-2">
        <form method="POST" action="{{ route('leagues.accept') }}">
          @csrf
          <input type="hidden" name="inviteID" value="{{ $invite->id }}">
          <button type="submit" class="btn btn-primary btn-sm" style="font-size:.78rem;padding:4px 14px;">Priimti</button>
        </form>
        <form method="POST" action="{{ route('leagues.decline') }}">
          @csrf
          <input type="hidden" name="inviteID" value="{{ $invite->id }}">
          <button type="submit" class="btn btn-sm" style="font-size:.78rem;padding:4px 14px;background:#f1f5f9;color:var(--sb-muted);border:1px solid var(--sb-border);">Atmesti</button>
        </form>
      </div>
    </div>
    @endforeach
  </div>
</div>
@endif

{{-- My Leagues --}}
<div class="sb-card">
  <div class="sb-card-title">
    <i class="bi bi-trophy sb-card-icon"></i>Mano lygos
  </div>
  <div class="row g-2">
    @foreach($myLeagues as $membership)
    @php $league = $membership->league; @endphp
    <div class="col-md-6">
      <div style="border:1.5px solid {{ $membership->active ? 'var(--sb-accent)' : 'var(--sb-border)' }};border-radius:10px;padding:14px 16px;background:{{ $membership->active ? '#eff6ff' : '#fff' }};height:100%;display:flex;flex-direction:column;gap:10px;">

        {{-- Name + badges --}}
        <div class="d-flex align-items-start justify-content-between gap-2">
          <div>
            <div style="font-size:.9rem;font-weight:700;color:var(--sb-text);line-height:1.3;">{{ $league->name }}</div>
            <div style="margin-top:5px;display:flex;gap:5px;flex-wrap:wrap;align-items:center;">
              @if($membership->active)
              <span style="font-size:.6rem;font-weight:700;background:var(--sb-accent);color:#fff;padding:2px 8px;border-radius:20px;letter-spacing:.4px;text-transform:uppercase;">Aktyvi</span>
              @endif
              @if($league->is_public)
              <span style="font-size:.6rem;font-weight:700;background:#f1f5f9;color:var(--sb-muted);padding:2px 8px;border-radius:20px;letter-spacing:.4px;text-transform:uppercase;border:1px solid var(--sb-border);">Vieša</span>
              @endif
            </div>
          </div>
          <div style="text-align:right;flex-shrink:0;">
            <div style="font-size:.78rem;color:var(--sb-muted);">
              <i class="bi bi-people" style="font-size:.72rem;"></i> {{ $league->members()->count() }}
            </div>
            @if($league->base_fee)
            <div style="font-size:.72rem;color:var(--sb-muted);margin-top:2px;">
              {{ $league->base_fee }}€{{ $league->penalty_step ? ' +' . $league->penalty_step . '€/vieta' : '' }}
            </div>
            @endif
          </div>
        </div>

        @if($league->description)
        <div style="font-size:.78rem;color:var(--sb-muted);line-height:1.45;">{{ $league->description }}</div>
        @endif

        {{-- Actions --}}
        <div class="d-flex gap-2 flex-wrap mt-auto">
          @if(!$membership->active)
          <form method="POST" action="{{ route('leagues.switch') }}">
            @csrf
            <input type="hidden" name="leagueID" value="{{ $league->id }}">
            <button type="submit" class="btn btn-primary btn-sm" style="font-size:.78rem;padding:5px 16px;">
              <i class="bi bi-arrow-repeat me-1"></i>Perjungti
            </button>
          </form>
          @endif

          @if($membership->is_admin)
          <button type="button"
                  class="btn btn-sm"
                  style="font-size:.78rem;padding:5px 14px;background:#fff;border:1.5px solid var(--sb-border);color:var(--sb-text);"
                  onclick="openManageModal({{ $league->id }}, {{ json_encode($league->name) }}, {{ $league->use_league_odds ? 'true' : 'false' }})">
            <i class="bi bi-gear me-1"></i>Valdyti
          </button>
          @endif

          @if(!$league->is_public)
            @if($league->owner_id !== session('userID'))
            <form method="POST" action="{{ route('leagues.leave') }}"
                  onsubmit="return confirm('Palikti lygą {{ addslashes($league->name) }}?')">
              @csrf
              <input type="hidden" name="leagueID" value="{{ $league->id }}">
              <button type="submit" class="btn btn-sm"
                      style="font-size:.78rem;padding:5px 14px;background:#fff;border:1.5px solid #fca5a5;color:#dc2626;">
                <i class="bi bi-box-arrow-right me-1"></i>Palikti
              </button>
            </form>
            @else
            <span style="font-size:.75rem;color:var(--sb-muted);align-self:center;">
              <i class="bi bi-star-fill" style="color:var(--sb-gold);font-size:.7rem;"></i> Savininkas
            </span>
            @endif
          @endif
        </div>

      </div>
    </div>
    @endforeach
  </div>
</div>

{{-- Create League --}}
<div class="sb-card">
  <div class="sb-card-title">
    <i class="bi bi-plus-circle sb-card-icon"></i>Sukurti naują lygą
  </div>
  <form method="POST" action="{{ route('leagues.create') }}">
    @csrf
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label" style="font-size:.78rem;font-weight:600;color:var(--sb-muted);">Pavadinimas <span style="color:#ef4444;">*</span></label>
        <input type="text" name="name" class="form-control form-control-sm" required maxlength="100"
               style="border-radius:8px;border-color:var(--sb-border);">
      </div>
      <div class="col-md-6">
        <label class="form-label" style="font-size:.78rem;font-weight:600;color:var(--sb-muted);">Aprašymas</label>
        <input type="text" name="description" class="form-control form-control-sm" maxlength="255"
               style="border-radius:8px;border-color:var(--sb-border);">
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label" style="font-size:.78rem;font-weight:600;color:var(--sb-muted);">Bazinė įmoka (€)</label>
        <input type="number" name="base_fee" class="form-control form-control-sm" min="0"
               style="border-radius:8px;border-color:var(--sb-border);">
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label" style="font-size:.78rem;font-weight:600;color:var(--sb-muted);">Bauda už vietą (€)</label>
        <input type="number" name="penalty_step" class="form-control form-control-sm" min="0"
               style="border-radius:8px;border-color:var(--sb-border);">
      </div>
      <div class="col-12">
        <button type="submit" class="btn btn-primary btn-sm" style="padding:7px 22px;font-size:.82rem;">
          <i class="bi bi-plus-lg me-1"></i>Sukurti lygą
        </button>
      </div>
    </div>
  </form>
</div>

{{-- Manage League Modal --}}
<div class="modal fade" id="manageModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border-radius:12px;overflow:hidden;">
      <div class="modal-header" style="background:var(--sb-nav);border-bottom:1px solid rgba(255,255,255,.1);">
        <h5 class="modal-title" id="manageModalTitle" style="color:#fff;font-size:.9rem;font-weight:700;">Valdyti lygą</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:20px 24px;">

        <div class="sb-card-title" style="margin-bottom:10px;">
          <i class="bi bi-person-plus sb-card-icon"></i>Pakviesti narį
        </div>
        <input type="text" id="inviteSearch" class="form-control form-control-sm mb-2"
               style="border-radius:8px;border-color:var(--sb-border);"
               placeholder="Ieškoti pagal vardą arba vartotojo vardą..."
               oninput="searchUsers(this.value)">
        <div id="searchResults" class="mb-3" style="border:1px solid var(--sb-border);border-radius:8px;overflow:hidden;display:none;"></div>

        <form id="inviteForm" method="POST" action="{{ route('leagues.invite') }}">
          @csrf
          <input type="hidden" id="inviteLeagueID" name="leagueID">
          <input type="hidden" id="invitedUserID" name="invitedUserID">
        </form>

        <div class="mt-3" style="border-top:1px solid var(--sb-border);padding-top:16px;">
          <div class="sb-card-title" style="margin-bottom:8px;">
            <i class="bi bi-percent sb-card-icon"></i>Koeficientai
          </div>
          <p style="font-size:.78rem;color:var(--sb-muted);margin-bottom:10px;">Per-lygos koeficientai aktyvuojami kai lyga turi ≥ 20 narių.</p>
          <form method="POST" action="{{ route('leagues.toggleOdds') }}" id="oddsToggleForm">
            @csrf
            <input type="hidden" name="leagueID" id="oddsLeagueID">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="use_league_odds" id="useLeagueOddsToggle"
                     value="1" onchange="document.getElementById('oddsToggleForm').submit()">
              <label class="form-check-label" for="useLeagueOddsToggle" style="font-size:.82rem;">
                Naudoti per-lygos koeficientus
              </label>
            </div>
          </form>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
let activeManageLeagueId = null;

function openManageModal(leagueId, leagueName, useLeagueOdds) {
    activeManageLeagueId = leagueId;
    document.getElementById('manageModalTitle').textContent = 'Valdyti: ' + leagueName;
    document.getElementById('inviteLeagueID').value = leagueId;
    document.getElementById('oddsLeagueID').value = leagueId;
    document.getElementById('useLeagueOddsToggle').checked = useLeagueOdds;
    const results = document.getElementById('searchResults');
    results.style.display = 'none';
    results.innerHTML = '';
    document.getElementById('inviteSearch').value = '';
    new bootstrap.Modal(document.getElementById('manageModal')).show();
}

function searchUsers(query) {
    const container = document.getElementById('searchResults');
    if (query.length < 2) {
        container.style.display = 'none';
        container.innerHTML = '';
        return;
    }
    fetch(`{{ route('leagues.searchUsers') }}?query=${encodeURIComponent(query)}&leagueID=${activeManageLeagueId}`)
        .then(r => r.json())
        .then(users => {
            if (users.length === 0) {
                container.style.display = 'block';
                container.innerHTML = '<div style="padding:10px 14px;font-size:.8rem;color:var(--sb-muted);">Nerasta</div>';
                return;
            }
            container.style.display = 'block';
            container.innerHTML = users.map((u, i) =>
                `<button type="button"
                         style="display:block;width:100%;text-align:left;padding:9px 14px;background:none;border:none;${i > 0 ? 'border-top:1px solid var(--sb-border);' : ''}font-size:.82rem;cursor:pointer;"
                         onmouseover="this.style.background='#f0f7ff'" onmouseout="this.style.background='none'"
                         onclick="selectInvitee(${u.id})">
                   <span style="font-weight:600;">${u.name} ${u.surname}</span>
                   <span style="color:var(--sb-muted);margin-left:6px;">${u.username}</span>
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
