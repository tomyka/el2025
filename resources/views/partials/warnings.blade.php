@if ($eventDaySurvivalStatus==0 && session('eventDay') != 0 && session('survivalGame')==1 && session('eventSurvival')==1)
    <div class="row">
        <div class="col-md-12"><B ><p id="warningtext">Vis dar nepasirinkote komandos Išlikimo žaidime {{session('eventDay')}} dienai</p></B></div>
    </div>
@endif

