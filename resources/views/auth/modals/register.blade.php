<!-- Registration Modals -->


            <div class="modal-body" style="padding-bottom: 0px">
                <form method="POST" action="{{ route('register') }}">
                    @csrf
                    <div class="form-group row">
                        <div class="col-md-12">
                            <input id="username" placeholder="Vartotojo vardas" type="text" class="form-control{{ $errors->has('username') ? ' is-invalid' : '' }}" name="username" value="{{ old('usernname') }}" required autofocus>
                            @if ($errors->has('username'))
                                <span class="invalid-feedback">
                                                        <strong>{{ $errors->first('username') }}</strong>
                                                    </span>
                            @endif
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-12">
                            <input id="firstname" placeholder="Vardas" type="text" class="form-control{{ $errors->has('firstname') ? ' is-invalid' : '' }}" name="firstname" value="{{ old('firstnname') }}" required autofocus>
                            @if ($errors->has('firstname'))
                                <span class="invalid-feedback">
                                                        <strong>{{ $errors->first('firstname') }}</strong>
                                                    </span>
                            @endif
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-12">
                            <input id="surname" placeholder="Pavardė" type="text" class="form-control{{ $errors->has('surname') ? ' is-invalid' : '' }}" name="surname" value="{{ old('surname') }}" autofocus>
                            @if ($errors->has('surname'))
                                <span class="invalid-feedback">
                                                        <strong>{{ $errors->first('surname') }}</strong>
                                                    </span>
                            @endif
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-12">
                            <input id="email" placeholder="Email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ old('email') }}" required>
                            @if ($errors->has('email'))
                                <span class="invalid-feedback">
                                                        <strong>{{ $errors->first('email') }}</strong>
                                                    </span>
                            @endif
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-12">
                            <input id="password" placeholder="Slaptažodis" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required>
                            @if ($errors->has('password'))
                                <span class="invalid-feedback">
                                                        <strong>{{ $errors->first('password') }}</strong>
                                                    </span>
                            @endif
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-12">
                            <input id="password-confirm" placeholder="Pakartokite slaptažodį" type="password" class="form-control" name="password_confirmation" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-12">
                            {{ Form::select('profileID',session('profiles'),null,['class'=>'form-select form-select'])}}
                        </div>
                    </div>
                    <div class="modal-footer">
                            <button type="submit" class="btn btn-outline-warning">
                                {{ __('Registruotis') }}
                            </button>
                    </div>
                </form>
            </div>
