<!-- Modal -->
<div class="modal fade" id="pswChangeModal" tabindex="-1" aria-labelledby="pswChangeModal" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Slaptažodžio keitimas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding-bottom: 0px">
                <form class="form" role="form"  id="loginmenu" method="post" action="{{ route('changeUserPassword') }}">
                    @csrf
                    <div class="form-group row">
                        <input id="emailInput" placeholder="Slaptažodis" class="form form-control" type="password" name="currentPassword" required>
                    </div>
                    <div class="form-group row">
                        <input id="passwordInput" placeholder="Naujas slaptažodis" class="form form-control" type="password" name="newPassword" required>
                    </div>
                    <div class="form-group row">
                        <input id="passwordInput" placeholder="Slaptažodžio patvirtinimas" class="form form-control" type="password" name="newPasswordConfirmation" required>
                    </div>
                    <div class="modal-footer">
                        <div class="col-md-12 text-center">
                            <div class="form-group"  id="loginmenu">
                                <button type="submit" class="btn btn-outline-success">Pakeisti</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>