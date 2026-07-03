
                @if ($groupDetails->base_fee > 0)
                    <div class="col col-12 col-sm-12 col-md-12 ">
                    @if(session('fee')==0)
                        <strong class="text-primary">
                        {{ __('Dalyvio mokestis:') }} {{ number_format($groupDetails->base_fee,2) }}€
                        </strong>
                        <BR>
                    @else
                        <span class="text-success">
                        {{ __('Startinis dalyvio mokestis sumokėtas. Sėkmės totalizatoriuje!') }}
                        </span>
                    @endif

                    </div>
                    <div class="col col-12 col-sm-12 col-md-12 text-muted">
                        {{ __('Dalyvių skaičius:') }} {{ $userDetails->users }}, {{ __('prognozuojama suma:') }} {{ number_format($fund,2) }}€, {{ __('surinkta suma:') }} {{ number_format($fundCollected,2) }}€
                    </div>
                @endif
                        <div class="col col-12 col-sm-12 col-md-12 text-muted">
                            {{$groupDetails->reward_description}}
                        </div>

