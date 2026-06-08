<div class="rules-page">

    {{-- ── Overview ───────────────────────────────────────────────── --}}
    <div class="sb-card">
        <div class="sb-card-title"><i class="bi bi-list-check sb-card-icon"></i> Totalizatoriaus eiga</div>

        <div class="rules-section">
            <p>Totalizatorius vykdomas dviem etapais:</p>

            <div class="rules-stage">
                <div class="rules-stage-badge">I</div>
                <div>
                    <strong>Grupių etapas</strong>
                    <p>Rungtynių rezultatų pogrupiuose ir vietų pogrupiuose spėjimas, komandų, patekusių į šešioliktfinalį, aštuntfinalį, ketvirtfinalį bei finalų išankstinis prognozavimas.</p>
                    <p>Rezultatus pateikti galima iki kiekvienų rungtynių pradžios.
                        @auth <a href="{{ route('prediction.standings') }}" class="rules-link">Turnyro eigos prognozė</a> @else Turnyro eigos prognozė @endauth
                        turi būti pateikta iki pirmųjų rungtynių pradžios.
                    </p>
                </div>
            </div>

            <div class="rules-stage">
                <div class="rules-stage-badge">II</div>
                <div>
                    <strong>Nokautų etapas</strong>
                    <p>Nuoseklus šešioliktfinalių, aštuntfinalių, ketvirtfinalių, pusfinalių ir finalo rezultatų spėjimas.</p>
                </div>
            </div>
        </div>

        @include('partials.fee')

        <div class="rules-section mt-3">
            <strong>Vertinimas</strong>
            <ul class="rules-list">
                <li>Prognozuojamų rezultatų tikslumas vertinamas taškais.</li>
                <li>Laimi daugiausiai surinkęs taškų.</li>
                <li>Jei keli dalyviai surenka po lygiai taškų, žiūrima į eigos prognozės taškus, po to į daugiausiai tiksliai atspėtų rungtynių skaičių.</li>
                <li>Neperdavus laiku rezultato, spėjimas bus sugeneruotas automatiškai — taškams už rungtynes nebus taikomas koeficientas.</li>
            </ul>
        </div>
    </div>

    {{-- ── Match scoring ───────────────────────────────────────────── --}}
    <div class="sb-card">
        <div class="sb-card-title"><i class="bi bi-award-fill sb-card-icon"></i> Taškų skaičiavimas — rungtynių rezultatai</div>

        <div class="rules-scoring-grid">
            <div class="rules-scoring-item">
                <div class="rules-scoring-pts">+5</div>
                <div class="rules-scoring-label">Atspėjus nugalėtoją</div>
            </div>
            <div class="rules-scoring-item">
                <div class="rules-scoring-pts">5 − Δ</div>
                <div class="rules-scoring-label">Tikslumas pagal įvarčių skirtumą</div>
            </div>
            <div class="rules-scoring-item">
                <div class="rules-scoring-pts">+2.5</div>
                <div class="rules-scoring-label">Bingo — tikslus rezultatas</div>
            </div>
        </div>

        <div class="rules-section">
            <strong>Koeficientas</strong>
            <ul class="rules-list">
                <li>Apskaičiuojamas: <code>1 + (1 − Spėjamų_baigties / Dalyvių_skaičius)</code></li>
                <li>Pvz.: 20 dalyvių, 7 spėjo A pergalę → A koeficientas <strong>1.65</strong>; 6 spėjo B pergalę → B koeficientas <strong>1.70</strong>.</li>
                <li>Koeficientas taikomas tik nugalėtojo taškams ir tik atspėjus nugalėtoją.</li>
            </ul>
            <p class="rules-formula">
                Taškai = (Nugalėtojas × Koeficientas) + Skirtumas + Bingo
            </p>
        </div>
    </div>

    {{-- ── Standings scoring ───────────────────────────────────────── --}}
    <div class="sb-card">
        <div class="sb-card-title"><i class="bi bi-diagram-3-fill sb-card-icon"></i> Taškų skaičiavimas — turnyro eiga</div>

        <div class="row g-4">

            {{-- Group stage position matrix --}}
            <div class="col-lg-4 col-md-6 col-12">
                <p class="rules-table-title">Grupių etapas — vietos</p>
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
                <p class="rules-table-caption">Eilutė = prognozė, stulpelis = tikroji vieta</p>
            </div>

            {{-- Knockout rounds --}}
            <div class="col-lg-4 col-md-6 col-12">
                <p class="rules-table-title">Nokautų etapas — patekimas</p>
                <table class="rules-table">
                    <thead>
                        <tr><th>Etapas</th><th>✓ atspėta</th><th>✗ neatspėta</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>1/16</td><td class="rules-pts-pos">3</td><td class="rules-pts-zero">0</td></tr>
                        <tr><td>1/8</td><td class="rules-pts-pos">6</td><td class="rules-pts-zero">0</td></tr>
                        <tr><td>Ketvirtfinalis</td><td class="rules-pts-pos">9</td><td class="rules-pts-zero">0</td></tr>
                    </tbody>
                </table>
            </div>

            {{-- Finals matrix --}}
            <div class="col-lg-4 col-md-6 col-12">
                <p class="rules-table-title">Finalai — vietos (prognozė vs tikroji)</p>
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
                <p class="rules-table-caption">Eilutė = prognozė, stulpelis = tikroji vieta</p>
            </div>

        </div>
    </div>

</div>
