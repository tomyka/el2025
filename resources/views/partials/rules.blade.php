<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <strong>Totalizatoriaus eiga</strong>
            <BR>
            Totalizatorius vykdomas dviem etapais:
            <BR><BR>
            <strong>I etapas</strong>
            <BR>
            Rungtynių rezultatų pogrupiuose ir vietų pogrupiuose spėjimas, komandų, patekusių į aštuntfinalį, ketvirtfinalį bei finalų išankstinis prognozavimas.
            <BR>
            Rezultatus pateikti galima iki kiekvienų rungtynių pradžios. @auth <a class="text-dark" href="{{route('prediction.standings')}}"><strong> @endauth Turnyro eigos prognozė turi būti pateikta iki pirmųjų varžybų pradžios. @auth </strong></a> @endauth
            <BR>
            <strong>II etapas</strong>
            <BR>
            Nuoseklus ketvirtfinalių ir finalo ketverto rezultatų spėjimas.<BR><BR>
            <strong>Vertinimas</strong><BR>
            Prognozuojamų rezultatų tikslumas vertinamas taškais (Taškų skaičiavimo sistema pateikiama žemiau).<BR>
            Laimi daugiausiai surinkęs taškų. Jei keli dalyviai surenka po lygiai taškų, žiūrima į eigos prognozės taškus, po to į daugiausiai tiksliai atspėtų rungtynių skaičių.<BR>
            Neperdavus laiku rezultato, spėjimas bus sugeneruotas automatiškai, tik taškams už rungtynes nebus taikomas koeficientas.<BR><BR>
        </div>
        @include('partials.fee')
    </div>
    <div class="row">
        <div class="col-md-3">
            <div class="hide-text-sm hide-text-md hide-text-lg">
            <BR>
            <strong>Taškai už vietas grupių etape</strong>
            <BR>
            <table class="table table-sm table-striped">
                <tr class="table-dark">
                    <td class="text-center" colspan=19><strong>Grupių etapas</strong></td>
                </tr>
                <tr>
                    <td class="table-active text-center"><B>&nbsp;</B></td>
                    @for ($i = 1; $i <= 4; $i++)
                        <td class="table-active text-center"><B>{{$i}}</B></td>

                    @endfor
                </tr>
                @for ($i = 1; $i <= 4; $i++)
                    <tr>
                        <td class="table-active text-center"><B>{{$i}}</B></td>
                        @for ($j = 1; $j <= 4; $j++)
                            <td class="table-default text-center">{{(3-abs($j-$i))*10}}</td>
                        @endfor
                    </tr>
              @endfor

            </table>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <strong>Taškai už turnyro eigos prognozę</strong>
        </div>
    </div>
    <div class="row">
        <div class="col-md-1">
            <table class="table table-sm table-bordered">
                <tr class="table-dark">
                    <td class="text-center justify-content-center" colspan=2><B>Ketvirtfinalis</B></td>
                </tr>
                <tr>
                    <td class="table-active cell_align_center cell_valign_middle"><B>&#10003;</B></td>
                    <td class="table-active cell_align_center cell_valign_middle"><B>&nbsp&#10005;</B></td>
                </tr>
                <tr>
                    <td class="table-default cell_align_center cell_valign_middle">3</td>
                    <td class="table-default cell_align_center cell_valign_middle">0</td>
                </tr>
            </table>
        </div>
        <div class="col-md-2">
            <table class="table table-sm table-bordered">
                <tr class="table-dark">
                    <td class="text-center justify-content-center" colspan=5><B>Finalai</B></td>
                </tr>
                <tr>
                    <td class="table-active text-center"><B>&nbsp;</B></td>
                    <td class="table-active text-center"><B>1</B></td>
                    <td class="table-active text-center"><B>2</B></td>
                    <td class="table-active text-center"><B>3</B></td>
                    <td class="table-active text-center"><B>4</B></td>
                </tr>
                <tr>
                    <td class="table-active text-center" ><B>1</B></td>
                    <td class="table-default text-center">36</td>
                    <td class="table-default text-center">27</td>
                    <td class="table-default text-center">18</td>
                    <td class="table-default text-center">9</td>
                </tr>
                <tr>
                    <td class="table-active text-center"><B>2</B></td>
                    <td class="table-default text-center">27</td>
                    <td class="table-default text-center">30</td>
                    <td class="table-default text-center">21</td>
                    <td class="table-default text-center">12</td>
                </tr>
                <tr>

                    <td class="table-active text-center"><B>3</B></td>
                    <td class="table-default text-center">18</td>
                    <td class="table-default text-center">21</td>
                    <td class="table-default text-center">24</td>
                    <td class="table-default text-center">15</td>
                </tr>
                <tr>

                    <td class="table-active text-center"><B>4</B></td>
                    <td class="table-default text-center">9</td>
                    <td class="table-default text-center">12</td>
                    <td class="table-default text-center">15</td>
                    <td class="table-default text-center">18</td>
                </tr>
            </table>
        </div>
    </div>
    <div class="row">
                <div class="col-md-12 col-12">
                    <BR>
                    <strong>Taškų už rungtynių rezultato spėjimą skaičiavimo sistema:</strong>
                    <BR>
                    <strong>Taškų skaičiavimas grupių etape:</strong>
                    <li>+5 balai atspėjus rungtynių baigtį (pergalė/lygiosios/pralaimėjimas)</li>
                    <li>0 balų neatspėjus rungtynių baigties.</li>
                    <strong>Koeficientas</strong>
                    <li>Koeficentas apskaičiuojamas taip: 1+(1-Spejamu_Baigties_Bendras_Skaičius/Dalyviu_Skaičius).</li>
                    <li>Pvz: Dalyvauja 20 dalyvių, kad laimės komanda A prognozuoja 7 dalyviai, kad laimės komanda B - 6 dalyviai, kad rungtynės baigsis lygiosiomis 7 dalyviai. Komandos A pergalės koeficientas yra 1.65, komandos B pergalės koeficientas yra 1.7, lygiųjų koeficientas 1.65.</li>
                    <strong>Taškai už rezultato spėjimą:</strong>
                    <li>Taškai imami iš taškų lentelės.</li>
                    <strong>Taškai už tikslius spėjimus:</strong>
                    <li>2.5 taško atspėjus tikslų rungtynių rezultatą.</li>
                    <strong>Bendras taškų skaičius už rungtynes:</strong>
                    <BR>
                    Taskai_uz_rungtynes = Taskai_uz_nugaletoja*Koeficientas+Taskai_uz_rezultata+Taskai_uz_tikslų_spėjimą.
                    <BR>
                    <BR>
                </div>
        </div>
</div>