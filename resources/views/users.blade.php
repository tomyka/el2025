@extends('layouts.master')
@section('content')

<div class="sb-card" style="max-width:560px; margin:0 auto;">
    <div class="sb-card-title"><i class="bi bi-people-fill sb-card-icon"></i> Dalyviai</div>

    @foreach($userGroups as $userGroup)
    <div class="lb-row">
        <div class="lb-rank lb-rank-n">{{ $loop->iteration }}</div>
        <div class="lb-name">
            <span class="fw-600">{{ $userGroup->user->username }}</span>
            @if($userGroup->user->name)
            <span class="text-muted ms-1" style="font-size:.78rem;font-weight:400;">{{ $userGroup->user->name }}</span>
            @endif
        </div>
    </div>
    @endforeach
</div>

@endsection
