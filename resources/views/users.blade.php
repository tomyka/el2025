@extends('layouts.master')
@section('content')
    <div class="container-fluid">
        <div class="row">
        <div class="col col-12 col-lg-6"  style=" margin-right: auto; margin-left: auto;" >
            <table class="table table-sm table-striped">
                <thead>
                    <tr class="table-dark">
                        <td class="text-center"><span>Dalyvis</span></td>
                        <td class="text-center"><span>Vardas</span></td>
                        <td class="text-center"><span>Paaukota</span></td>
                    </tr>
                </thead>

                <tbody class="table-default">
                    @foreach($userGroups as $userGroup)
                    <tr>
                        <td><B>{{$userGroup->user->username}}</B></td>
                        <td>{{$userGroup->user->name}}</td>
                        <td class="text-center">{{$userGroup->fee}}</td>
                    </tr>

                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    </div>

@endsection
