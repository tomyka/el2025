@php
    $firstGame = \App\Models\Game::min('game_date');
    $registrationOpen = is_null($firstGame) || now()->lt($firstGame);
    $openRegTab = $errors->hasAny(['username', 'name', 'surname']);
@endphp

<div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
        <div class="modal-content border-0 shadow-lg" style="border-radius:12px;overflow:hidden">

            {{-- Dark branded header --}}
            <div class="sb-auth-header">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="material-icons" style="color:var(--sb-accent);font-size:1.25rem;">sports_soccer</span>
                        <span class="fw-bold" style="color:#fff;letter-spacing:.5px;font-size:.95rem;">SportBet</span>
                    </div>
                    <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
                </div>

                {{-- Tabs --}}
                <div class="d-flex gap-3">
                    <button class="nav-link sb-auth-tab {{ $openRegTab ? '' : 'active' }}"
                            data-bs-toggle="tab" data-bs-target="#loginPane" type="button">
                        Prisijungti
                    </button>
                    @if ($registrationOpen)
                    <button class="nav-link sb-auth-tab {{ $openRegTab ? 'active' : '' }}"
                            data-bs-toggle="tab" data-bs-target="#registerPane" type="button">
                        Registruotis
                    </button>
                    @endif
                </div>
            </div>

            {{-- Panes --}}
            <div class="tab-content">
                <div class="tab-pane fade {{ $openRegTab ? '' : 'show active' }}" id="loginPane" role="tabpanel">
                    @include('modals.login')
                </div>
                @if ($registrationOpen)
                <div class="tab-pane fade {{ $openRegTab ? 'show active' : '' }}" id="registerPane" role="tabpanel">
                    @include('modals.register')
                </div>
                @endif
            </div>

        </div>
    </div>
</div>

@if ($errors->any())
<script>
document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('loginModal');
    if (el) bootstrap.Modal.getOrCreateInstance(el).show();
});
</script>
@endif
