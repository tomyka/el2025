@extends('layouts.master')
@section('content')

<div class="sb-card cmp-card">
    @include('compare._card')
    <div class="cmp-back mt-3">
        <a href="{{ route('main') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> {{ __('Atgal') }}
        </a>
    </div>
</div>

@endsection
