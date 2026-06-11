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

{{-- Pending invites --}}
@if($pendingInvites->count())
<div class="sb-card">
  <div class="sb-card-title">
    <i class="bi bi-envelope-fill sb-card-icon"></i>
    Kvietimai
    <span class="badge ms-1" style="background:var(--sb-accent);font-size:.62rem;vertical-align:middle;">{{ $pendingInvites->count() }}</span>
  </div>
  <div class="d-flex flex-column gap-2">
    @foreach($pendingInvites as $invite)
    <div class="d-flex align-items-center justify-content-between p-2"
         style="background:#f8fafc;border-radius:8px;border:1px solid var(--sb-border);">
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
          <button type="submit" class="btn btn-sm"
                  style="font-size:.78rem;padding:4px 14px;background:#f1f5f9;color:var(--sb-muted);border:1px solid var(--sb-border);">Atmesti</button>
        </form>
      </div>
    </div>
    @endforeach
  </div>
</div>
@endif

{{-- Leagues list --}}
<div class="sb-card" style="overflow:hidden;">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div class="sb-card-title" style="margin-bottom:0;">
      <i class="bi bi-trophy sb-card-icon"></i>Mano lygos
    </div>
    <button type="button" class="btn btn-primary btn-sm"
            style="font-size:.78rem;padding:5px 14px;flex-shrink:0;"
            onclick="openCreateModal()">
      <i class="bi bi-plus-lg me-1"></i>Sukurti lygą
    </button>
  </div>

  @forelse($myLeagues as $membership)
  @php $league = $membership->league; $isOwner = $league->owner_id === session('userID'); @endphp
  <div class="league-row {{ $membership->active ? 'league-row-active' : '' }}">

    {{-- Name + badges --}}
    <div class="league-row-main">
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="league-row-name">{{ $league->name }}</span>
        @if($isOwner)
          <span class="league-status-badge owner"><i class="bi bi-star-fill"></i> Savininkas</span>
        @else
          <span class="league-status-badge member">Narys</span>
        @endif
        <span class="league-member-count"><i class="bi bi-people"></i> {{ $league->members()->count() }}</span>
      </div>
      @if($league->description)
      <span class="league-row-desc">{{ $league->description }}</span>
      @endif
    </div>

    {{-- Actions --}}
    <div class="league-row-actions">
      @if(!$membership->active)
      <form method="POST" action="{{ route('leagues.switch') }}" style="display:contents;">
        @csrf
        <input type="hidden" name="leagueID" value="{{ $league->id }}">
        <button type="submit" class="league-action-btn" title="Perjungti į šią lygą"
                style="color:var(--sb-accent);border-color:var(--sb-accent);">
          <i class="bi bi-toggle-off"></i>
        </button>
      </form>
      @endif

      @if($membership->is_admin)
      @php
        $memberList = $league->members->map(fn($m) => [
            'display'  => trim(($m->user->name ?? '') . ' ' . ($m->user->surname ?? '')),
            'username' => $m->user->username ?? '',
            'is_admin' => $m->is_admin,
        ])->values();
        $pendingList = $league->pendingInvites->map(fn($i) => [
            'display'  => trim(($i->invitedUser->name ?? '') . ' ' . ($i->invitedUser->surname ?? '')),
            'username' => $i->invitedUser->username ?? '',
        ])->values();
      @endphp
      <button type="button" class="league-action-btn" title="Pakviesti narį"
              style="color:var(--sb-accent);border-color:var(--sb-accent);"
              onclick="openInviteModal({{ $league->id }}, {{ json_encode($league->name) }}, {{ json_encode($memberList) }}, {{ json_encode($pendingList) }})">
        <i class="bi bi-person-plus"></i>
      </button>
      <button type="button" class="league-action-btn" title="Redaguoti lygą"
              style="color:var(--sb-muted);border-color:var(--sb-border);"
              onclick="openEditModal({{ $league->id }}, {{ json_encode($league->name) }}, {{ json_encode($league->description ?? '') }}, {{ (int)($league->base_fee ?? 0) }}, {{ (int)($league->penalty_step ?? 0) }}, {{ $league->use_league_odds ? 'true' : 'false' }})">
        <i class="bi bi-gear"></i>
      </button>
      @endif

      @if(!$league->is_public && !$isOwner)
      <form method="POST" action="{{ route('leagues.leave') }}" style="display:contents;"
            onsubmit="return confirm('Palikti lygą {{ addslashes($league->name) }}?')">
        @csrf
        <input type="hidden" name="leagueID" value="{{ $league->id }}">
        <button type="submit" class="league-action-btn" title="Palikti lygą"
                style="color:#dc2626;border-color:#fca5a5;">
          <i class="bi bi-box-arrow-right"></i>
        </button>
      </form>
      @endif
    </div>

  </div>
  @empty
  <div style="padding:20px 0;text-align:center;color:var(--sb-muted);font-size:.85rem;">
    Dar nepriklausote jokiai lygai.
  </div>
  @endforelse
</div>

<style>
.league-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 4px;
  border-top: 1px solid var(--sb-border);
}
.league-row:first-of-type { border-top: none; }
.league-row-active {
  background: #eff6ff;
  border-radius: 8px;
  padding: 11px 10px;
  margin: 0 -4px;
  border-top: none !important;
  border-left: 3px solid var(--sb-accent);
}
.league-row-active + .league-row { border-top: none; }

.league-row-main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.league-row-name {
  font-size: .875rem;
  font-weight: 700;
  color: var(--sb-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.league-row-desc {
  font-size: .72rem;
  color: var(--sb-muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.league-status-badge {
  font-size: .65rem;
  font-weight: 700;
  padding: 2px 9px;
  border-radius: 20px;
  white-space: nowrap;
}
.league-status-badge.owner {
  background: #fefce8;
  color: #a16207;
  border: 1px solid #fde68a;
}
.league-status-badge.member {
  background: #f1f5f9;
  color: var(--sb-muted);
  border: 1px solid var(--sb-border);
}
.league-member-count {
  font-size: .78rem;
  color: var(--sb-muted);
  white-space: nowrap;
}
.league-member-count i { font-size: .7rem; }

.league-row-actions {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
}
.league-action-btn {
  width: 30px;
  height: 30px;
  border-radius: 7px;
  border: 1.5px solid;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: .85rem;
  cursor: pointer;
  transition: background .12s, opacity .12s;
  padding: 0;
}
.league-action-btn:hover { opacity: .75; }
</style>

{{-- ── Invite Modal ── --}}
<div class="modal fade" id="inviteModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content" style="border-radius:12px;overflow:hidden;">
      <div class="modal-header" style="background:var(--sb-nav);border-bottom:1px solid rgba(255,255,255,.1);padding:14px 20px;">
        <h5 class="modal-title" id="inviteModalTitle" style="color:#fff;font-size:.88rem;font-weight:700;">
          <i class="bi bi-person-plus me-2"></i>Pakviesti narį
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:18px 20px;">
        <p id="inviteModalLeagueName" style="font-size:.95rem;font-weight:700;color:var(--sb-text);margin-bottom:12px;"></p>
        <input type="text" id="inviteSearch" class="form-control form-control-sm"
               style="border-radius:8px;border-color:var(--sb-border);"
               placeholder="Ieškoti vartotojo..."
               oninput="searchUsers(this.value)">
        <div id="searchResults" style="border:1px solid var(--sb-border);border-radius:8px;overflow:hidden;display:none;margin-top:6px;"></div>
        <div id="memberList" style="margin-top:14px;display:none;">
          <div style="font-size:.72rem;font-weight:700;color:var(--sb-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">Nariai</div>
          <div id="memberListItems" style="display:flex;flex-direction:column;gap:4px;max-height:140px;overflow-y:auto;"></div>
        </div>
        <div id="pendingList" style="margin-top:14px;display:none;">
          <div style="font-size:.72rem;font-weight:700;color:var(--sb-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">Laukiama atsakymo</div>
          <div id="pendingListItems" style="display:flex;flex-direction:column;gap:4px;max-height:100px;overflow-y:auto;"></div>
        </div>
        <form id="inviteForm" method="POST" action="{{ route('leagues.invite') }}">
          @csrf
          <input type="hidden" id="inviteLeagueID" name="leagueID">
          <input type="hidden" id="invitedUserID" name="invitedUserID">
        </form>
      </div>
    </div>
  </div>
</div>

{{-- ── Create League Modal ── --}}
<div class="modal fade" id="createModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="border-radius:12px;overflow:hidden;">
      <div class="modal-header" style="background:var(--sb-nav);border-bottom:1px solid rgba(255,255,255,.1);padding:14px 20px;">
        <h5 class="modal-title" style="color:#fff;font-size:.88rem;font-weight:700;">
          <i class="bi bi-plus-circle me-2"></i>Sukurti naują lygą
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:20px 24px;">
        <form method="POST" action="{{ route('leagues.create') }}">
          @csrf
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label" style="font-size:.78rem;font-weight:600;color:var(--sb-muted);">Pavadinimas <span style="color:#ef4444;">*</span></label>
              <input type="text" name="name" class="form-control form-control-sm" required maxlength="100"
                     style="border-radius:8px;border-color:var(--sb-border);">
            </div>
            <div class="col-12">
              <label class="form-label" style="font-size:.78rem;font-weight:600;color:var(--sb-muted);">Aprašymas</label>
              <input type="text" name="description" class="form-control form-control-sm" maxlength="255"
                     style="border-radius:8px;border-color:var(--sb-border);">
            </div>
            <div class="col-6">
              <label class="form-label" style="font-size:.78rem;font-weight:600;color:var(--sb-muted);">Dalyvio mokestis (€)</label>
              <input type="number" name="base_fee" class="form-control form-control-sm" min="0"
                     style="border-radius:8px;border-color:var(--sb-border);">
            </div>
            <div class="col-6">
              <label class="form-label" style="font-size:.78rem;font-weight:600;color:var(--sb-muted);">Papildomas mokestis už vietą (€)</label>
              <input type="number" name="penalty_step" class="form-control form-control-sm" min="0"
                     style="border-radius:8px;border-color:var(--sb-border);">
            </div>
            <div class="col-12">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="use_league_odds" id="createLeagueOdds" value="1">
                <label class="form-check-label" for="createLeagueOdds" style="font-size:.82rem;">
                  Lygos koeficientai
                  <span style="font-size:.72rem;color:var(--sb-muted);">(aktyvuojami kai lyga turi ≥ 20 narių)</span>
                </label>
              </div>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-sm"
                      style="font-size:.82rem;padding:6px 18px;background:#f1f5f9;color:var(--sb-muted);border:1px solid var(--sb-border);"
                      data-bs-dismiss="modal">Atšaukti</button>
              <button type="submit" class="btn btn-primary btn-sm" style="padding:6px 22px;font-size:.82rem;">
                <i class="bi bi-plus-lg me-1"></i>Sukurti
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

{{-- ── Edit League Modal (admin only) ── --}}
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="border-radius:12px;overflow:hidden;">
      <div class="modal-header" style="background:var(--sb-nav);border-bottom:1px solid rgba(255,255,255,.1);padding:14px 20px;">
        <h5 class="modal-title" id="editModalTitle" style="color:#fff;font-size:.88rem;font-weight:700;">
          <i class="bi bi-pencil me-2"></i>Redaguoti lygą
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:20px 24px;">
        <form method="POST" action="{{ route('leagues.update') }}" id="editForm">
          @csrf
          <input type="hidden" name="leagueID" id="editLeagueID">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label" style="font-size:.78rem;font-weight:600;color:var(--sb-muted);">Pavadinimas <span style="color:#ef4444;">*</span></label>
              <input type="text" name="name" id="editName" class="form-control form-control-sm" required maxlength="100"
                     style="border-radius:8px;border-color:var(--sb-border);">
            </div>
            <div class="col-12">
              <label class="form-label" style="font-size:.78rem;font-weight:600;color:var(--sb-muted);">Aprašymas</label>
              <input type="text" name="description" id="editDescription" class="form-control form-control-sm" maxlength="255"
                     style="border-radius:8px;border-color:var(--sb-border);">
            </div>
            <div class="col-6">
              <label class="form-label" style="font-size:.78rem;font-weight:600;color:var(--sb-muted);">Dalyvio mokestis (€)</label>
              <input type="number" name="base_fee" id="editBaseFee" class="form-control form-control-sm" min="0"
                     style="border-radius:8px;border-color:var(--sb-border);">
            </div>
            <div class="col-6">
              <label class="form-label" style="font-size:.78rem;font-weight:600;color:var(--sb-muted);">Papildomas mokestis už vietą (€)</label>
              <input type="number" name="penalty_step" id="editPenaltyStep" class="form-control form-control-sm" min="0"
                     style="border-radius:8px;border-color:var(--sb-border);">
            </div>
            <div class="col-12">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="use_league_odds" id="editLeagueOdds" value="1">
                <label class="form-check-label" for="editLeagueOdds" style="font-size:.82rem;">
                  Lygos koeficientai
                  <span style="font-size:.72rem;color:var(--sb-muted);">(aktyvuojami kai lyga turi ≥ 20 narių)</span>
                </label>
              </div>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-sm"
                      style="font-size:.82rem;padding:6px 18px;background:#f1f5f9;color:var(--sb-muted);border:1px solid var(--sb-border);"
                      data-bs-dismiss="modal">Atšaukti</button>
              <button type="submit" class="btn btn-primary btn-sm" style="padding:6px 22px;font-size:.82rem;">
                <i class="bi bi-check-lg me-1"></i>Išsaugoti
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
let activeInviteLeagueId = null;

function openCreateModal() {
    new bootstrap.Modal(document.getElementById('createModal')).show();
}

function openInviteModal(leagueId, leagueName, members, pending) {
    activeInviteLeagueId = leagueId;
    document.getElementById('inviteLeagueID').value = leagueId;
    document.getElementById('inviteModalTitle').innerHTML = '<i class="bi bi-person-plus me-2"></i>Pakviesti narį';
    document.getElementById('inviteModalLeagueName').textContent = leagueName;
    const r = document.getElementById('searchResults');
    r.style.display = 'none';
    r.innerHTML = '';
    document.getElementById('inviteSearch').value = '';

    const memberListEl = document.getElementById('memberList');
    const memberItemsEl = document.getElementById('memberListItems');
    if (members && members.length) {
        memberItemsEl.innerHTML = members.map(m =>
            `<div style="display:flex;align-items:center;gap:6px;padding:4px 8px;background:#f8fafc;border-radius:6px;font-size:.78rem;">
               <span style="font-weight:600;">${m.display}</span>
               <span style="color:var(--sb-muted);">${m.username}</span>
               ${m.is_admin ? '<span style="font-size:.65rem;background:#fefce8;color:#a16207;border:1px solid #fde68a;border-radius:10px;padding:1px 7px;font-weight:700;">Admin</span>' : ''}
             </div>`
        ).join('');
        memberListEl.style.display = 'block';
    } else {
        memberListEl.style.display = 'none';
    }

    const pendingListEl = document.getElementById('pendingList');
    const pendingItemsEl = document.getElementById('pendingListItems');
    if (pending && pending.length) {
        pendingItemsEl.innerHTML = pending.map(p =>
            `<div style="display:flex;align-items:center;gap:6px;padding:4px 8px;background:#fffbeb;border-radius:6px;font-size:.78rem;border:1px solid #fde68a;">
               <span style="font-weight:600;">${p.display}</span>
               <span style="color:var(--sb-muted);">${p.username}</span>
             </div>`
        ).join('');
        pendingListEl.style.display = 'block';
    } else {
        pendingListEl.style.display = 'none';
    }

    new bootstrap.Modal(document.getElementById('inviteModal')).show();
    setTimeout(() => document.getElementById('inviteSearch').focus(), 300);
}

function openEditModal(leagueId, leagueName, leagueDesc, baseFee, penaltyStep, useLeagueOdds) {
    document.getElementById('editModalTitle').innerHTML = '<i class="bi bi-pencil me-2"></i>' + leagueName;
    document.getElementById('editLeagueID').value = leagueId;
    document.getElementById('editName').value = leagueName;
    document.getElementById('editDescription').value = leagueDesc || '';
    document.getElementById('editBaseFee').value = baseFee || '';
    document.getElementById('editPenaltyStep').value = penaltyStep || '';
    document.getElementById('editLeagueOdds').checked = useLeagueOdds;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function searchUsers(query) {
    const container = document.getElementById('searchResults');
    if (query.length < 2) {
        container.style.display = 'none';
        container.innerHTML = '';
        return;
    }
    fetch(`{{ route('leagues.searchUsers') }}?query=${encodeURIComponent(query)}&leagueID=${activeInviteLeagueId}`)
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
