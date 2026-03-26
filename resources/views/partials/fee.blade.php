
                @if ($groupDetails->fee!="")
                    <div class="col col-12 col-sm-12 col-md-12 ">
                    @if(session('fee')==0)
                        <strong class="text-primary">
                        {{$groupDetails->fee_description}}
                        </strong>
                        <BR>
                    @else
                        <span class="text-success">
                        {{"Startinis dalyvio mokestis sumokėtas. Sėkmės totalizatoriuje!"}}
                        </span>
                    @endif

                    </div>
                    <div class="col col-12 col-sm-12 col-md-12 text-muted">
                        {{'Dalyvių skaičius: '.$userDetails->users.', prognozuojama suma: '.number_format($fund,2).'€, surinkta suma: '.number_format($fundCollected,2).'€'}}
                    </div>
                @endif
                        <div class="col col-12 col-sm-12 col-md-12 text-muted">
                            {{$groupDetails->reward_description}}
                        </div>

