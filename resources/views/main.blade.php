@extends('layouts.master')
@section('content')

    <div class="container-fluid">

        @auth

            @if(session('disabled')=="")
                <div class="row">

                </div>
                <BR>
                <div class="row">
                    <div class="col-md-12 col-12">
                        <B>Taisyklės:</B>
                    </div>
                    @include('partials.rules')
                </div>
            @else
                <div class="card border-primary">
                    <div class="col col-12 col-md-12">
                        @include('partials.fee')
                    </div>
                    <div class="col col-12 col-md-12">
                        @include('partials.messages')
                    </div>
                    <div class="col col-md-12">
                        @include('partials.warnings')
                    </div>
                </div>
                <BR>
                <div class="row">
                    @if (session('eventRate')==2)
                        <div class="col col-xl-5 col-lg-6 col-md-12 col-l-12">
                    @else
                        <div class="col col-xl-6 col-lg-6 col-md-12 col-l-12">
                    @endif
                    @if (session('eventID')!=0)
                        @include('partials.games')
                        @include('partials.previous')
                        @include('partials.standings')
                    @else
                        @include('partials.points')
                    @endif

                    </div>

                    @if (session('eventRate')==2)
                        <div class="col col-xl-4 col-lg-6 col-md-12 col-l-12">
                    @else
                        <div class="col col-xl-6 col-lg-6 col-md-12 col-l-12">
                    @endif

                    @if (session('eventID')!=0)
                            @include('partials.points')
                    @else
                            @include('partials.pointsStandings')
                   @endif
                    </div>

                    @if (session('eventRate')==2)
                        <div class="col col-xl-3 col-lg-6 col-md-12 col-l-12">
                            @include('partials.pointsStandings')
                        </div>
                    @endif


            @endif
        @else
            @include('welcome')
        @endauth

</div>
@endsection
