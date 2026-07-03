@if ($eventDaySurvivalStatus==0 && session('eventDay') != 0 && session('survivalGame')==1 && session('eventSurvival')==1)
<div class="alert alert-warning d-flex align-items-center gap-2 mb-2 py-2">
    <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
    <span>{{ __('Vis dar nepasirinkote komandos Išlikimo žaidime :day dienai.', ['day' => session('eventDay')]) }}</span>
</div>
@endif

@if (!empty($standingsMissing) && session('disabled') !== 'disabled')
<div class="alert alert-warning d-flex align-items-center gap-2 mb-2 py-2">
    <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
    <span>
        {{ __('Turnyro eigos prognozė nepilnai užpildyta.') }}
        <a href="{{ route('prediction.standings') }}" class="alert-link">{{ __('Užpildyti') }}&nbsp;→</a>
    </span>
</div>
@endif
