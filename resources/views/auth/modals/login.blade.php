


            <div class="modal-body" style="padding-bottom: 0px">

                <form class="form" role="form"  id="loginmenu" method="post" action="{{ route('login') }}">
                    @csrf
                    <div class="form-group row">
                        <input id="emailInput" placeholder="Vartotojo vardas" class="form form-control" type="text" name="username">
                    </div>
                    <div class="form-group row">
                        <input id="passwordInput" placeholder="Slaptažodis" class="form form-control" type="password" name="password">
                    </div>
                    <div class="modal-footer">
                                <div class="form-group"  id="loginmenu">
                                    <button type="submit" class="btn btn-outline-warning mr-auto">Prisijungti</button>
                                </div>
                    </div>
                </form>
            </div>
