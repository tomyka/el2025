@extends('layouts.master')
@section('content')

@if(Session::has('info'))
<div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
    {{ Session::get('info') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('status') === 'password-updated')
<div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
    Slaptažodis sėkmingai pakeistas.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-3">

    {{-- Profile info --}}
    <div class="col-lg-6 col-12">
        <div class="sb-card h-100">
            <div class="sb-card-title">Profilio informacija</div>

            @if($errors->any() && !$errors->hasBag('updatePassword'))
            <div class="alert alert-danger py-2 mb-3">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li style="font-size:.83rem">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="{{ route('userProfile') }}">
                @csrf
                <input type="hidden" name="userID" value="{{ $user->id }}">

                <div class="profile-field">
                    <label class="profile-label">Vartotojo vardas</label>
                    <input class="form-control form-control-sm profile-input" type="text" name="username" value="{{ old('username', $user->username) }}" required>
                </div>
                <div class="profile-field">
                    <label class="profile-label">Vardas</label>
                    <input class="form-control form-control-sm profile-input" type="text" name="name" value="{{ old('name', $user->name) }}" required>
                </div>
                <div class="profile-field">
                    <label class="profile-label">Pavardė</label>
                    <input class="form-control form-control-sm profile-input" type="text" name="surname" value="{{ old('surname', $user->surname) }}" required>
                </div>
                <div class="profile-field">
                    <label class="profile-label">El. paštas</label>
                    <input class="form-control form-control-sm profile-input" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                </div>

                <div class="mt-3 text-end">
                    <button type="submit" class="btn btn-sm btn-primary">Išsaugoti</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Password change --}}
    <div class="col-lg-6 col-12">
        <div class="sb-card h-100">
            <div class="sb-card-title">Slaptažodžio keitimas</div>

            @if($errors->hasBag('updatePassword'))
            <div class="alert alert-danger py-2 mb-3">
                <ul class="mb-0 ps-3">
                    @foreach($errors->getBag('updatePassword')->all() as $error)
                        <li style="font-size:.83rem">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                @method('PUT')

                <div class="profile-field">
                    <label class="profile-label">Dabartinis</label>
                    <input class="form-control form-control-sm profile-input" type="password" name="current_password" autocomplete="current-password" required>
                </div>
                <div class="profile-field">
                    <label class="profile-label">Naujas</label>
                    <input class="form-control form-control-sm profile-input" type="password" name="password" autocomplete="new-password" required>
                </div>
                <div class="profile-field">
                    <label class="profile-label">Pakartoti</label>
                    <input class="form-control form-control-sm profile-input" type="password" name="password_confirmation" autocomplete="new-password" required>
                </div>

                <div class="mt-3 text-end">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Keisti slaptažodį</button>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection
