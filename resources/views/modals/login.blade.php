


            <div class="modal-body" style="padding-bottom: 0px">

                <form class="form" role="form"  id="loginmenu" method="post" action="{{ route('login') }}">
                    @csrf
                    <div class="form-group row">
                        <input id="email" placeholder="Email" class="form form-control" type="text" name="email">
                    </div>
                    <div class="form-group row">
                        <input id="password" placeholder="Slaptažodis" class="form form-control" type="password" name="password">
                    </div>
                    <div class="modal-footer">
                                <div class="form-group"  id="loginmenu">
                                    <button type="submit" class="btn btn-outline-primary mr-auto">Prisijungti</button>
                                </div>
                    </div>
                </form>

                <div class="text-center px-3 pb-3">
                    <hr class="my-2">
                    <a href="{{ route('auth.google') }}" class="btn btn-outline-secondary w-100">
                        <img src="https://www.svgrepo.com/show/475656/google-color.svg"
                             width="18" height="18" class="me-2" alt="Google">
                        Prisijungti su Google
                    </a>
                </div>

            </div>
