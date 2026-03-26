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
        @auth
            @include('partials.fee')
        @endauth
    </div>
    <div class="row">
        <div class="col-md-3">
            <div class="hide-text-sm hide-text-md hide-text-lg">
                <BR>
                <strong>Taškai už vietas grupių etape</strong>
                <BR>
                <table class="table table-sm table-striped">
                    <tr class="table-dark">
                        <td class="text-center" colspan=21><strong>Grupių etapas</strong></td>
                    </tr>
                    <tr>
                        <td class="table-active text-center"><B>&nbsp;</B></td>
                        @for ($i = 1; $i <= 20; $i++)
                            <td class="table-active text-center"><B>{{$i}}</B></td>

                        @endfor
                    </tr>
                    @for ($i = 1; $i <= 20; $i++)
                        <tr>
                            <td class="table-active text-center"><B>{{$i}}</B></td>
                            @for ($j = 1; $j <= 20; $j++)
                                <td class="table-default text-center">{{(19-abs($j-$i))*10}}</td>
                            @endfor
                        </tr>
                    @endfor

                </table>
            </div>
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
                    <td class="table-light cell_align_center cell_valign_middle"><B>&#10003;</B></td>
                    <td class="table-light cell_align_center cell_valign_middle"><B>&nbsp&#10005;</B></td>
                </tr>
                <tr>
                    <td class="table-default cell_align_center cell_valign_middle">30</td>
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
                <td class="table-light text-center"><B>&nbsp;</B></td>
                <td class="table-light text-center"><B>1</B></td>
                <td class="table-light text-center"><B>2</B></td>
                <td class="table-light text-center"><B>3</B></td>
                <td class="table-light text-center"><B>4</B></td>
            </tr>
            <tr>
                <td class="table-light text-center" ><B>1</B></td>
                <td class="table-default text-center">720</td>
                <td class="table-default text-center">480</td>
                <td class="table-default text-center">300</td>
                <td class="table-default text-center">180</td>
            </tr>
            <tr>
                <td class="table-active text-center"><B>2</B></td>
                <td class="table-default text-center">480</td>
                <td class="table-default text-center">600</td>
                <td class="table-default text-center">360</td>
                <td class="table-default text-center">240</td>
            </tr>
            <tr>

                <td class="table-active text-center"><B>3</B></td>
                <td class="table-default text-center">300</td>
                <td class="table-default text-center">360</td>
                <td class="table-default text-center">540</td>
                <td class="table-default text-center">330</td>
            </tr>
            <tr>

                <td class="table-active text-center"><B>4</B></td>
                <td class="table-default text-center">180</td>
                <td class="table-default text-center">240</td>
                <td class="table-default text-center">330</td>
                <td class="table-default text-center">450</td>
            </tr>
        </table>
    </div>
    </div>
<div class="row">
    <div class="col-md-12 col-12">
        <BR>
        <strong>Balų už rungtynių rezultato spėjimą skaičiavimo sistema:</strong>
        <BR>
        <strong>Balai už atspėtą nugalėtoją (BuN):</strong>
        <li>50 balų atspėjus rungtynių nugalėtoją.</li>
        <li>0 balų neatspėjus rungtynių nugalėtojo.</li>
        <strong>Koeficientas (K)</strong>
        <li>Koeficentas apskaičiuojamas taip: 1+(1-Spejamu_Baigties_Bendras_Skaičius/Dalyviu_Skaičius).</li>
        <li>Pvz: Dalyvauja 20 dalyvių, kad laimės komanda A prognozuoja 7 dalyviai, kad laimės komanda B - 13 dalyvių. Komandos A pergalės koeficientas yra 1.65, komandos B pergalės koeficientas yra 1.35.</li>
        <strong>Balai už skirtumo spėjimą (BuS):</strong>
        <li>50 balų atspėjus skirtumą tiksliai.</li>
        <li>50-ABS(rungtynių_rezultato_skirtumas-spetas_skirtumas). Suklydus daugiau nei 50 taškų bus skaičiuojamas neigiamas balų skaičius.</li>
        <strong>Balai už tikslius spėjimus (BuTS):</strong>
        <li>50 balų atspėjus tikslų rungtynių rezultatą.</li>
        <li>20 balų atspėjus tikslų rungtynių skirtumą.</li>
        <li>0 balų neatspėjųs tikslaus rezultato ar skirtumo</li>
        <strong>Bendras balų skaičius už rungtynes:</strong>
        <BR>
        Balai_uz_rungtynes = BuN*K+BuS+BuTS.
        <BR>
        Pavyzdys: žaidžia komanda A ir komanda B. Rungtynės baigiasi 68-65 A komandos naudai. Koeficientas yra 1.
        <BR>
        Scenarijus #1: prognozuojate, kad rungtynes rezultatu 68-65 laimės komanda A.
        <BR>
        Balai=50*1+50-ABS(3-3)+50=150 (tai yra maksimalus taškų skaičius už vienerias rungtynes, nevertinant koeficiento įtakos).
        <BR>
        Scenarijus #2: prognozuojate, kad rungtynes rezultatu 68-65 laimės komanda B.
        <BR>
        Balai=0*1+50-ABS(68-65)-(65-68))+0=0+44+0=44
        <BR>
        Scenarijus #3: prognozuojate, kad rungtynes rezultatu 78-75 laimės komanda B.
        <BR>
        Balai=50*1+50-ABS(3-3)+20=50+50+20=120
        <BR>
    </div>
</div>

