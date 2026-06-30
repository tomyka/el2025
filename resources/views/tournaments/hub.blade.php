@extends('layouts.master')
@section('content')
<div>Turnyrai</div>
@guest
@include('modals.main')
@endguest
@endsection
