<div class="rules-page">

    {{-- ── Overview ───────────────────────────────────────────────── --}}
    <div class="sb-card">
        <div class="sb-card-title"><i class="bi bi-list-check sb-card-icon"></i> {{ __('Totalizatoriaus eiga') }}</div>

        <div class="rules-section">
            <p>{{ __('Totalizatorius vykdomas dviem etapais:') }}</p>

            <div class="rules-stage">
                <div class="rules-stage-badge">I</div>
                <div>
                    <strong>{{ __('Grupių etapas') }}</strong>
                    <p>{{ __('Rungtynių rezultatų pogrupiuose ir vietų pogrupiuose spėjimas, komandų, patekusių į šešioliktfinalį, aštuntfinalį, ketvirtfinalį bei finalų išankstinis prognozavimas.') }}</p>
                    <p>{{ __('Rezultatus pateikti galima iki kiekvienų rungtynių pradžios.') }}
                        @auth <a href="{{ route('prediction.standings') }}" class="rules-link">{{ __('Turnyro eigos prognozė') }}</a> @else {{ __('Turnyro eigos prognozė') }} @endauth
                        {{ __('turi būti pateikta iki pirmųjų rungtynių pradžios.') }}
                    </p>
                </div>
            </div>

            <div class="rules-stage">
                <div class="rules-stage-badge">II</div>
                <div>
                    <strong>{{ __('Atkrintamųjų etapas') }}</strong>
                    <p>{{ __('Nuoseklus šešioliktfinalių, aštuntfinalių, ketvirtfinalių, pusfinalių ir finalo rezultatų spėjimas.') }}</p>
                </div>
            </div>
        </div>

        @include('partials.fee')

        <div class="rules-section mt-3">
            <strong>{{ __('Vertinimas') }}</strong>
            <ul class="rules-list">
                <li>{{ __('Prognozuojamų rezultatų tikslumas vertinamas taškais.') }}</li>
                <li>{{ __('Laimi daugiausiai surinkęs taškų.') }}</li>
                <li>{{ __('Jei keli dalyviai surenka po lygiai taškų, žiūrima į eigos prognozės taškus, po to į daugiausiai tiksliai atspėtų rungtynių skaičių.') }}</li>
                <li>{{ __('Neperdavus laiku rezultato, spėjimas bus sugeneruotas automatiškai — taškams už rungtynes nebus taikomas koeficientas.') }}</li>
            </ul>
        </div>
    </div>

    {{-- ── Match scoring ───────────────────────────────────────────── --}}
    <div class="sb-card">
        <div class="sb-card-title"><i class="bi bi-award-fill sb-card-icon"></i> {{ __('Taškų skaičiavimas — rungtynių rezultatai') }}</div>

        <div class="rules-scoring-grid">
            <div class="rules-scoring-item">
                <div class="rules-scoring-pts">(1+Koef.)×5</div>
                <div class="rules-scoring-label">{{ __('Nugalėtojas — su koeficiento premija') }}</div>
            </div>
            <div class="rules-scoring-item">
                <div class="rules-scoring-pts">5 − Δ</div>
                <div class="rules-scoring-label">{{ __('Tikslumas pagal įvarčių skirtumą') }}</div>
            </div>
            <div class="rules-scoring-item">
                <div class="rules-scoring-pts">+2.5</div>
                <div class="rules-scoring-label">{{ __('Bingo — tikslus rezultatas') }}</div>
            </div>
            <div class="rules-scoring-item">
                <div class="rules-scoring-pts">+N−1</div>
                <div class="rules-scoring-label">{{ __('Seka — N atspėjimų iš eilės') }}</div>
            </div>
            <div class="rules-scoring-item">
                <div class="rules-scoring-pts">×1 / ×2</div>
                <div class="rules-scoring-label">{{ __('Etapo koeficientas (Grupių etapas / Atkrintamosios)') }}</div>
            </div>
        </div>

        <div class="rules-section">
            <p class="rules-formula">
                {{ __('Taškai = (Nugalėtojas + Tikslumas + Bingo + Seka) × Etapo_koef') }}
            </p>
        </div>
    </div>

    {{-- ── Accuracy matrix ────────────────────────────────────────── --}}
    <div class="sb-card">
        <div class="sb-card-title"><i class="bi bi-grid-3x3-gap-fill sb-card-icon"></i> {{ __('Tikslumo taškų lentelė') }}</div>

        <div class="rules-section">
            <p>{{ __('Eilutė — namų komandos prognozės klaida, stulpelis — svečių komandos prognozės klaida (ta pati kryptis). Priešinga kryptis arba didesnė klaida mažina taškus.') }}</p>

            <div style="overflow-x:auto;">
            <table class="rules-table mt-2">
                <thead>
                    <tr>
                        <th>{{ __('Namų Δ \\ Svečių Δ') }}</th>
                        <th>0</th><th>1</th><th>2</th><th>3</th><th>4</th><th>5</th><th>6</th><th>7</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><th>0</th><td class="rules-pts-pos">5.0</td><td>4.0</td><td>3.0</td><td>2.0</td><td>1.0</td><td class="rules-pts-zero">0.0</td><td class="rules-pts-zero">−1.0</td><td class="rules-pts-zero">−2.0</td></tr>
                    <tr><th>1</th><td>4.0</td><td class="rules-pts-pos">4.5</td><td>3.5</td><td>2.5</td><td>1.5</td><td>0.5</td><td class="rules-pts-zero">−0.5</td><td class="rules-pts-zero">−1.5</td></tr>
                    <tr><th>2</th><td>3.0</td><td>3.5</td><td class="rules-pts-pos">4.0</td><td>3.0</td><td>2.0</td><td>1.0</td><td class="rules-pts-zero">0.0</td><td class="rules-pts-zero">−1.0</td></tr>
                    <tr><th>3</th><td>2.0</td><td>2.5</td><td>3.0</td><td class="rules-pts-pos">3.5</td><td>2.5</td><td>1.5</td><td>0.5</td><td class="rules-pts-zero">−0.5</td></tr>
                    <tr><th>4</th><td>1.0</td><td>1.5</td><td>2.0</td><td>2.5</td><td class="rules-pts-pos">3.0</td><td>2.0</td><td>1.0</td><td class="rules-pts-zero">0.0</td></tr>
                    <tr><th>5</th><td class="rules-pts-zero">0.0</td><td>0.5</td><td>1.0</td><td>1.5</td><td>2.0</td><td class="rules-pts-pos">2.5</td><td>1.5</td><td>0.5</td></tr>
                    <tr><th>6</th><td class="rules-pts-zero">−1.0</td><td class="rules-pts-zero">−0.5</td><td class="rules-pts-zero">0.0</td><td>0.5</td><td>1.0</td><td>1.5</td><td class="rules-pts-pos">2.0</td><td>1.0</td></tr>
                    <tr><th>7</th><td class="rules-pts-zero">−2.0</td><td class="rules-pts-zero">−1.5</td><td class="rules-pts-zero">−1.0</td><td class="rules-pts-zero">−0.5</td><td class="rules-pts-zero">0.0</td><td>0.5</td><td>1.0</td><td class="rules-pts-pos">1.5</td></tr>
                </tbody>
            </table>
            </div>
            <p class="rules-table-caption">{{ __('Pažymėta įstrižainė — lygi klaida abiejose pusėse (teisingas įvarčių skirtumas). Pilka — nulinis arba neigiamas taškai.') }}</p>
        </div>
    </div>

    {{-- ── Odds ────────────────────────────────────────────────────── --}}
    <div class="sb-card">
        <div class="sb-card-title"><i class="bi bi-graph-up sb-card-icon"></i> {{ __('Koeficientas') }}</div>

        <div class="rules-section">
            <p>{{ __('Koeficientas atlygina už drąsų, retai atspėtą spėjimą — kuo mažiau dalyvių pasirinko tą baigtį, tuo daugiau taškų už nugalėtoją.') }}</p>
            <ul class="rules-list">
                <li>{{ __('Koef. formulė:') }} <code>log₂(Dalyvių_skaičius / Spėjusiųjų_šią_baigtį)</code></li>
                <li>{{ __('Taikomas tik atspėjus nugalėtoją.') }}</li>
                <li>{{ __('Automatiškai sugeneruotiems spėjimams koeficientas = 0') }}</li>
            </ul>

            <table class="rules-table mt-3" style="max-width:340px;">
                <thead>
                    <tr><th>{{ __('Spėjo šią baigtį') }}</th><th>{{ __('Koef.') }}</th><th>{{ __('Nugalėtojas') }}</th></tr>
                </thead>
                <tbody>
                    <tr><td>93 % (28/30)</td><td>0.10</td><td>(1+0.10)×5 = <strong>5.5</strong></td></tr>
                    <tr><td>50 % (15/30)</td><td>1.00</td><td>(1+1.00)×5 = <strong>10.0</strong></td></tr>
                    <tr><td>33 % (10/30)</td><td>1.58</td><td>(1+1.58)×5 = <strong>12.9</strong></td></tr>
                    <tr><td>10 % (3/30)</td><td>3.32</td><td>(1+3.32)×5 = <strong>21.6</strong></td></tr>
                    <tr><td>3 % (1/30)</td><td>4.91</td><td>(1+4.91)×5 = <strong>29.5</strong></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Streak ───────────────────────────────────────────────────── --}}
    <div class="sb-card">
        <div class="sb-card-title"><i class="bi bi-lightning-fill sb-card-icon"></i> {{ __('Sekos premija') }}</div>

        <div class="rules-section">
            <p>{{ __('Už kiekvieną iš eilės teisingai atspėtą nugalėtoją kaupiama sekos premija.') }}</p>
            <ul class="rules-list">
                <li>{{ __('Skaičiuojama pagal rungtynių eilę (ID tvarka).') }}</li>
                <li>{{ __('Vienas teisingas spėjimas iš eilės — premijos nėra.') }}</li>
                <li>{{ __('Kiekvienas papildomas teisingas spėjimas iš eilės prideda') }} <strong>+1</strong>.</li>
                <li>{{ __('Sekos premija dauginama iš etapo koeficiento kartu su kitais taškais.') }}</li>
            </ul>

            <table class="rules-table mt-3" style="max-width:300px;">
                <thead>
                    <tr><th>{{ __('Atspėjimų iš eilės') }}</th><th>{{ __('Sekos premija') }}</th></tr>
                </thead>
                <tbody>
                    <tr><td>1</td><td>+0</td></tr>
                    <tr><td>2</td><td>+1</td></tr>
                    <tr><td>3</td><td>+2</td></tr>
                    <tr><td>N</td><td>+(N−1)</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Standings scoring ───────────────────────────────────────── --}}
    <div class="sb-card">
        <div class="sb-card-title"><i class="bi bi-diagram-3-fill sb-card-icon"></i> {{ __('Taškų skaičiavimas — turnyro eiga') }}</div>

        <div class="row g-4">

            {{-- Group stage position matrix --}}
            <div class="col-lg-4 col-md-6 col-12">
                <p class="rules-table-title">{{ __('Grupių etapas — vietos') }}</p>
                <table class="rules-table">
                    <thead>
                        <tr>
                            <th></th>
                            @for($i = 1; $i <= 4; $i++)<th>{{ $i }}</th>@endfor
                        </tr>
                    </thead>
                    <tbody>
                        @for($i = 1; $i <= 4; $i++)
                        <tr>
                            <th>{{ $i }}</th>
                            @for($j = 1; $j <= 4; $j++)
                            <td class="{{ (3 - abs($j - $i)) > 0 ? 'rules-pts-pos' : 'rules-pts-zero' }}">
                                {{ (3 - abs($j - $i)) }}
                            </td>
                            @endfor
                        </tr>
                        @endfor
                    </tbody>
                </table>
                <p class="rules-table-caption">{{ __('Eilutė = prognozė, stulpelis = tikroji vieta') }}</p>
            </div>

            {{-- Knockout rounds --}}
            <div class="col-lg-4 col-md-6 col-12">
                <p class="rules-table-title">{{ __('Nokautų etapas — patekimas') }}</p>
                <table class="rules-table">
                    <thead>
                        <tr><th>{{ __('Etapas') }}</th><th><i class="bi bi-check-lg"></i> {{ __('atspėta') }}</th><th><i class="bi bi-x-lg"></i> {{ __('neatspėta') }}</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>{{ __('Šešioliktfinalis') }}</td><td class="rules-pts-pos">3</td><td class="rules-pts-zero">0</td></tr>
                        <tr><td>{{ __('Aštuntfinalis') }}</td><td class="rules-pts-pos">6</td><td class="rules-pts-zero">0</td></tr>
                        <tr><td>{{ __('Ketvirtfinalis') }}</td><td class="rules-pts-pos">9</td><td class="rules-pts-zero">0</td></tr>
                    </tbody>
                </table>
            </div>

            {{-- Finals matrix --}}
            <div class="col-lg-4 col-md-6 col-12">
                <p class="rules-table-title">{{ __('Finalai — vietos (prognozė vs tikroji)') }}</p>
                <table class="rules-table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>1</th><th>2</th><th>3</th><th>4</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><th>1</th><td class="rules-pts-pos">36</td><td>27</td><td>18</td><td>9</td></tr>
                        <tr><th>2</th><td>27</td><td class="rules-pts-pos">30</td><td>21</td><td>12</td></tr>
                        <tr><th>3</th><td>18</td><td>21</td><td class="rules-pts-pos">24</td><td>15</td></tr>
                        <tr><th>4</th><td>9</td><td>12</td><td>15</td><td class="rules-pts-pos">18</td></tr>
                    </tbody>
                </table>
                <p class="rules-table-caption">{{ __('Eilutė = prognozė, stulpelis = tikroji vieta') }}</p>
            </div>

        </div>
    </div>

</div>
