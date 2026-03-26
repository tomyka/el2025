@extends('layouts.master')
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-xl-12">
            Noriu padėkoti visiems dalyvaujantiems ir skiriantiems lėšas labdarai. Jūsų dėka JAU pavyko surinkti ir paaukoti Jaunimo Linijai 7500€ (iki 2024 metų visą surinktą paramą dvigubino TransUnion Lithuania įmonė).<strong> Ačiū, kad prisidedate prie gerų darbų!</strong>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12 col-xl-6 text-center">
                <BR>
                <a href="https://www.jaunimolinija.lt/" target="_blank">
                    <img src="{{URL::to('img/JL_thank.png')}}"  width=100%>
                </a>
                <BR>
                <BR>
            </div>
            <div class="col-lg-12 col-xl-6 text-center">
                <strong>Nuo 2018 metų surinkta parama:</strong>
                <BR>
                <img src="{{URL::to('img/JL_payment_el2025.png')}}" alt="Euroleague2025 payment" width=100%>
                <BR>
                <img src="{{URL::to('img/JL_payment_el2024.png')}}" alt="Euroleague2024 payment" width=100%>
                <BR>
                <img src="{{URL::to('img/JL_payment_el2023.png')}}" alt="Euroleague2023 payment" width=100%>
                <BR>
                <img src="{{URL::to('img/JL_payment_el2022.png')}}" alt="Euroleague2022 payment" width=100%>
                <BR>
                <img src="{{URL::to('img/JL_payment_ecb2022.png')}}" alt="Eurobasket2022 payment" width=100%>
                <BR>
                <img src="{{URL::to('img/JL_payment_el2021.png')}}" alt="Euroleague2021 payment" width=100%>
                <BR>
                <img src="{{URL::to('img/JL_payment_ecf2020.png')}}" alt="Euro2020 payment" width=100%>
                <BR>
                <img src="{{URL::to('img/JL_payment_el2020.png')}}" alt="Euroleague2020 payment" width=100%>
                <BR>
                <img src="{{URL::to('img/JL_payment_el2019.png')}}" alt="Euroleague2019 payment" width=100%>
                <BR>
                <img src="{{URL::to('img/JL_payment_el2018_2.png')}}" alt="Euroleague2018 payment" width=100%>
                <BR>
                <img src="{{URL::to('img/JL_payment_el2018_1.png')}}" alt="Euroleague2018 payment" width=100%>
            </div>
        </div>
    </div>
    <BR>
@endsection
