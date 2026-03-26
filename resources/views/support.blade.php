@extends('layouts.master')
@section('content')

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-7 col-12">
            Nuo šiol Sportbet svetainė perkelta į Hostinger serverius, kas padės užtikrinti greitesnį svetainės veikimą ir stabilumą. Jei norite norite ir galite prisidėti prie svetainės išlaikymo - tą padaryti galite per Contribee platformą adresu:
                <a href="http://contribee.com/order/sportbet/one-time">Sportbet</a> arba nuskanavę QR kodą.
                <BR>
                Ačiū iš anksto, kad dalyvaujate bei suteikiate galimybę kurti ir remti Jaunimo liniją.
            </div>
            <div class="col-lg-5 col-12 text-center">
                <span class="d-sm-none">
                    <BR>
                </span>
                <img src="{{URL::to('img/contribee.png')}}" width="60%" alt="Contribee">
            </div>
        </div>
    </div>
@endsection