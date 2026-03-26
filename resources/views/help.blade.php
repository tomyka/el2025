@extends('layouts.master')
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <strong><i class="bi bi-dribbble h4"></i> Rezultatai</strong> - privaloma užpildyti rezultato prognozę iki rungtynių pradžios. Daugiau informacijos rasite rezultatų prognozės puslapyje.
                <BR>
                <img src="{{URL::to('img/user_results.png')}}" width="40%">
                <BR><BR>
                <strong><i class="bi bi-table h4"></i> Eiga</strong> - privaloma užpildyti viso čemppionato eigos prognozę iki pirmųjų varžybų pradžios. Daugiau informacijos rasite rezultatų prognozės puslapyje.
                <BR>
                <img src="{{URL::to('img/user_table.png')}}" width="40%">
                <BR><BR>
                @if(session('survivalGame')==1)
                    <strong>Išlikimas</strong> - kiekvieno turo metu reikia pasirinkti vieną komandą, kuri jūsų manymu nugalės. Daugiau informacijos rasite rezultatų prognozės puslapyje.
                    <BR>
                    <img src="{{URL::to('img/user_survival.png')}}" width="40%">
                    <BR><BR>
                @endif
                <strong><i class="bi bi-people-fill h4"></i> Dalyviai</strong> - totalizatoriaus dalyvių sąrašas.
                <BR>
                <strong><i class="bi bi-info-circle h4"></i> Info</strong> - informacija.
                <ul>
                    <li><strong>Taisyklės</strong> - totalizatoriaus taisyklės.</li>
                    <li><strong>Pagalba</strong> - totalizatoriaus naudojimos pagalba.</li>
                    <li><strong>Jaunimo Linija</strong> - informacija apie paramą Jaunimo Linijai.</li>
                    <li><strong>Parama</strong> - paremkite Sportbet!</li>
                </ul>
                <strong><i class="bi bi-file-earmark-bar-graph-fill h4"></i> Suvestinė</strong> - totalizatoriaus suvestinė (prieinamas tik prasidėjus totalizatoriui).<BR>
                <ul>
                    <li><strong>Įvykusios varžybos</strong> - jau įvykusių varžybų sąrašas su gautais taškais.</li>
                    <li><strong>Rezultatų suvestinė</strong> - totalizatoriaus dalyvių rungtynių prognozių suvestinė.</li>
                    <li><strong>Eigos suvestinė</strong> - totalizatoriaus dalyvių eigos prognozių suvestinė.</li>
                    <li><strong>Grafikas</strong> - varžybų dalyvių taškų ir vietų kitimo dinamika.</li>
                </ul>
            </div>
        </div>
    </div>
@endsection