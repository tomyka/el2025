
        Detali taškų už eigą lentelė:
        <table class="table table-sm table-bordered">
            <thead>
                <tr class="table-primary">
                        <td class="text-center"><strong>Komanda</strong></td>
                        <td class="text-center"><strong>Grupė</strong></td>
<!--                        <th class="text-center text-body"><strong>1/8</strong></th> -->
                        <td class="text-center"><strong>Playoff</strong></td>
                        <td class="text-center"><strong>Final4</strong></td>
                </tr>
            </thead>
            <tbody>

                @foreach($predictionStandingsPoints as $predictionStandingsPoint)

                    <tr class="table-default">
                        <td class="text-left text-body">{{$predictionStandingsPoint->team}}</td>
                        <td class="text-center text-body">{{$predictionStandingsPoint->group_position_points}}</td>
<!--                        <td class="text-center text-body">{{$predictionStandingsPoint->last16_points}}</td> -->
                        <td class="text-center text-body">{{$predictionStandingsPoint->quarterfinal_points}}</td>
                        <td class="text-center text-body">{{$predictionStandingsPoint->final_points}}</td>
                    </tr>

                    @endforeach

            </tbody>
        </table>





