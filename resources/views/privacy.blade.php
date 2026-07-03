@extends('layouts.master')
@section('content')
<div class="sb-card" style="max-width:760px;margin:0 auto;">
    <div class="sb-card-title"><i class="bi bi-shield-lock sb-card-icon"></i> {{ __('Privatumo politika') }}</div>
    <div style="line-height:1.7;font-size:.92rem;color:var(--sb-text);">

        <p style="color:var(--sb-muted);font-size:.82rem;">{{ __('Atnaujinta: 2026 m. birželio mėn.') }}</p>

        <h6 class="mt-3">{{ __('1. Kas mes esame') }}</h6>
        <p>{{ __('SportBet yra sporto prognozių žaidimas, skirtas asmeniniam naudojimui tarp draugų ir kolegų. Administratorius: Tomas Konovalovas, el. paštas:') }} <a href="mailto:t.konovalovas@gmail.com">t.konovalovas@gmail.com</a>.</p>

        <h6 class="mt-3">{{ __('2. Kokius duomenis renkame') }}</h6>
        <ul>
            <li><strong>{{ __('Paskyros duomenys') }}</strong> — {{ __('el. pašto adresas ir vartotojo vardas, kuriuos pateikiate registruodamiesi.') }}</li>
            <li><strong>{{ __('Prognozės') }}</strong> — {{ __('jūsų įvesti rungtynių rezultatų ir turnyro eigos spėjimai.') }}</li>
            <li><strong>{{ __('Sesijos duomenys') }}</strong> — {{ __('prisijungimo sesija saugoma naršyklės slapuke (cookie), kad galėtumėte naudotis svetaine.') }}</li>
            <li><strong>{{ __('Techniniai duomenys') }}</strong> — {{ __('IP adresas ir naršyklės informacija, automatiškai perduodama serverio žurnalams.') }}</li>
        </ul>

        <h6 class="mt-3">{{ __('3. Kaip naudojame duomenis') }}</h6>
        <ul>
            <li>{{ __('Valdyti jūsų paskyrą ir rodyti asmeninę statistiką.') }}</li>
            <li>{{ __('Skaičiuoti taškus ir formuoti lyderių lentelę.') }}</li>
            <li>{{ __('Siųsti svarbius pranešimus apie žaidimą (pvz., turnyro pradžią).') }}</li>
        </ul>

        <h6 class="mt-3">{{ __('4. Reklama ir trečiųjų šalių slapukai') }}</h6>
        <p>{{ __('Ši svetainė naudoja') }} <strong>Google AdSense</strong> {{ __('reklamai rodyti. Google ir jos partneriai gali naudoti slapukus, kad rodytų jums aktualesnę reklamą, remdamiesi jūsų ankstesniais apsilankymais šioje ir kitose svetainėse.') }}</p>
        <p>{{ __('Daugiau apie tai, kaip Google naudoja duomenis, rasite:') }} <a href="https://policies.google.com/technologies/ads" target="_blank" rel="noopener">policies.google.com/technologies/ads</a>.</p>
        <p>{{ __('Reklamų personalizavimą galite išjungti:') }} <a href="https://www.google.com/settings/ads" target="_blank" rel="noopener">google.com/settings/ads</a>.</p>

        <h6 class="mt-3">{{ __('5. Slapukai (cookies)') }}</h6>
        <p>{{ __('Naudojame šiuos slapukus:') }}</p>
        <ul>
            <li><strong>{{ __('Sesijos slapukas') }}</strong> — {{ __('būtinas prisijungimui palaikyti. Ištrinamas uždarius naršyklę arba atsijungus.') }}</li>
            <li><strong>{{ __('Google AdSense slapukai') }}</strong> — {{ __('reklamos personalizavimui ir statistikai. Valdomi „Google LLC".') }}</li>
        </ul>
        <p>{{ __('Slapukus galite išjungti naršyklės nustatymuose, tačiau tada gali neveikti prisijungimas.') }}</p>

        <h6 class="mt-3">{{ __('6. Duomenų saugojimas') }}</h6>
        <p>{{ __('Paskyros duomenys saugomi tol, kol paskyra yra aktyvi arba kol pateikiate prašymą juos ištrinti. Techniniai žurnalai saugomi ne ilgiau nei 90 dienų.') }}</p>

        <h6 class="mt-3">{{ __('7. Jūsų teisės (BDAR / GDPR)') }}</h6>
        <p>{{ __('Turite teisę:') }}</p>
        <ul>
            <li>{{ __('Susipažinti su savo asmens duomenimis.') }}</li>
            <li>{{ __('Reikalauti juos ištaisyti arba ištrinti.') }}</li>
            <li>{{ __('Atšaukti sutikimą dėl duomenų tvarkymo.') }}</li>
            <li>{{ __('Pateikti skundą Valstybinei duomenų apsaugos inspekcijai (') }}<a href="https://vdai.lrv.lt" target="_blank" rel="noopener">vdai.lrv.lt</a>{{ __(').') }}</li>
        </ul>
        <p>{{ __('Kreipkitės el. paštu:') }} <a href="mailto:t.konovalovas@gmail.com">t.konovalovas@gmail.com</a>.</p>

        <h6 class="mt-3">{{ __('8. Pakeitimai') }}</h6>
        <p>{{ __('Apie esminius privatumo politikos pakeitimus informuosime per svetainę. Toliau naudodamiesi svetaine, sutinkate su nauja politika.') }}</p>

    </div>
</div>
@endsection
