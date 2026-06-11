@extends('layouts.master')
@section('content')
<div class="rules-page">

    {{-- ── Spėjimai ────────────────────────────────────────────────── --}}
    <div class="sb-card">
        <div class="sb-card-title"><i class="bi bi-pencil-square sb-card-icon"></i> Spėjimai</div>

        <div class="rules-section">
            <div class="rules-stage">
                <div class="rules-stage-badge"><i class="material-icons" style="font-size:1rem;vertical-align:middle;">sports_soccer</i></div>
                <div>
                    <strong>Rungtynių rezultatai</strong>
                    <p>Kiekvienų rungtynių rezultatą reikia atspėti iki jų pradžios. Jei spėjimas nepateikiamas laiku — jis sugeneruojamas automatiškai ir taškams nebus taikomas odds koeficientas.</p>
                    <img src="{{ URL::to('img/user_results.png') }}" class="help-screenshot" alt="Rungtynių rezultatų spėjimas">
                </div>
            </div>

            <div class="rules-stage">
                <div class="rules-stage-badge"><i class="bi bi-table"></i></div>
                <div>
                    <strong>Turnyro eiga</strong>
                    <p>Viso čempionato eigos prognozė — grupių vietos, patekimas į atskiras fazes — turi būti pateikta iki pirmųjų varžybų pradžios.</p>
                    <img src="{{ URL::to('img/user_table.png') }}" class="help-screenshot" alt="Turnyro eigos spėjimas">
                </div>
            </div>

            @if(session('survivalGame') == 1)
            <div class="rules-stage">
                <div class="rules-stage-badge"><i class="bi bi-bullseye"></i></div>
                <div>
                    <strong>Išlikimas</strong>
                    <p>Kiekvieno turo metu reikia pasirinkti vieną komandą, kuri laimės. Klysti galima tik vieną kartą — klaida pašalina iš išlikimo žaidimo.</p>
                    <img src="{{ URL::to('img/user_survival.png') }}" class="help-screenshot" alt="Išlikimo spėjimas">
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- ── Suvestinė ───────────────────────────────────────────────── --}}
    <div class="sb-card">
        <div class="sb-card-title"><i class="bi bi-bar-chart-line sb-card-icon"></i> Suvestinė</div>

        <div class="rules-section">
            <p>Suvestinė prieinama tik prasidėjus totalizatoriui.</p>
            <ul class="rules-list">
                <li><strong><i class="bi bi-list-check"></i> Prognozės</strong> — visų dalyvių rungtynių spėjimų ir gautų taškų suvestinė.</li>
                <li><strong><i class="bi bi-table"></i> Eiga</strong> — dalyvių turnyro eigos prognozių suvestinė.</li>
                @if(session('survivalGame') != 0)
                <li><strong><i class="bi bi-shield-check"></i> Išlikimas</strong> — išlikimo žaidimo suvestinė.</li>
                @endif
                <li><strong><i class="bi bi-bar-chart-line"></i> Grafikas</strong> — dalyvių taškų ir vietų kitimo dinamika per visą turnyrą.</li>
            </ul>
        </div>
    </div>

    {{-- ── Kita ────────────────────────────────────────────────────── --}}
    <div class="sb-card">
        <div class="sb-card-title"><i class="bi bi-info-circle sb-card-icon"></i> Informacija</div>

        <div class="rules-section">
            <ul class="rules-list">
                <li><strong><i class="bi bi-journal-text"></i> Taisyklės</strong> — taškų skaičiavimo taisyklės ir pilnas žaidimo aprašymas.</li>
                <li><strong><i class="bi bi-question-circle"></i> Pagalba</strong> — šis puslapis.</li>
                <li><strong><i class="bi bi-heart"></i> Jaunimo linija</strong> — informacija apie paramą Jaunimo Linijai.</li>
                <li><strong><i class="bi bi-shield-lock"></i> Privatumas</strong> — privatumo politika.</li>
            </ul>
        </div>
    </div>

</div>

<style>
.help-screenshot {
    display: block;
    max-width: 480px;
    width: 100%;
    margin-top: .75rem;
    border-radius: .5rem;
    border: 1px solid var(--sb-border, #e2e8f0);
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
}
</style>
@endsection
