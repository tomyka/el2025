@extends('layouts.master')
@section('content')
    @auth
        <div class="container-fluid">
            @if (count($errors->all()))
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-danger">
                            <ul>
                                @foreach($errors->all() as $error)
                                    <li>{{$error}}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif
            @if (Session::has('info'))
                <div class="row">
                    <div class="col-md-12">
                        <p class="alert alert-primary">{{Session::get('info')}}</p>
                    </div>
                </div>
            @endif

            @empty($predictionResults)
            {{"Nėra rungtynių."}}
            @else
                    @foreach($predictionResults as $predictionResult)
                        <form id="prediction_result{{$predictionResult['gameDetails']->game_id}}" method="POST" action="">
                            @csrf
                            <div class="row">
                                <div class="col col-lg-12 col-md-12 " style="display: flex; flex-wrap: wrap; justify-content: space-evenly;">
                                    <div class="card" style="width:34rem;">
                                        <div class="card-body">
                                            <div class="row">
                                                {{csrf_field()}}
                                                <input type="hidden" name="prediction_gameID" id="prediction_gameID{{$predictionResult['gameDetails']->game_id}}" value = "{{$predictionResult['gameDetails']->id}}">
                                                <input type="hidden" name="gameID" value = "{{$predictionResult['gameDetails']->game_id}}">
                                                <input type="hidden" name="gameDate" value = "{{$predictionResult['gameDetails']->game_date}}">
                                                <input type="hidden" name="homeTeam" value = "{{$predictionResult['gameDetails']->home_team}}">
                                                <input type="hidden" name="awayTeam" value = "{{$predictionResult['gameDetails']->away_team}}">

                                                <div class="col col-lg-4 col-md-4 col-sm-3 col-3 text-center">
                                                        <img src="{{URL::to('img/Teams/'.$predictionResult['gameDetails']->home_team.'.png')}}" alt="homeTeam" height=70>
                                                </div>

                                                <div class="col col-lg-4 col-md-4 col-sm-4 col-6 text-center">
                                                    <h6>{{ substr($predictionResult['gameDetails']->game_date,0,16)}}</h6>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control input-size-2" size=1 name="homeTeamScore" onkeyup="checkPrediction({{$predictionResult['gameDetails']->game_id}})" id="homeTeamScore{{$predictionResult['gameDetails']->game_id}}" value="{{$predictionResult['gameDetails']->home_team_score}}">
                                                        <h3>&nbsp;:&nbsp;</h3>
                                                        <input type="text" class="form-control input-size-2" size=1 name="awayTeamScore" onkeyup="checkPrediction({{$predictionResult['gameDetails']->game_id}})" id="awayTeamScore{{$predictionResult['gameDetails']->game_id}}" value="{{$predictionResult['gameDetails']->away_team_score}}">
                                                    </div>
                                                </div>
                                                <div class="col col-lg-4 col-md-4 col-sm-3 col-3 text-center">
                                                        <img src="{{URL::to('img/Teams/'.$predictionResult['gameDetails']->away_team.'.png')}}" alt="awayTeam" height=70>
                                                </div>
                                            </div>
                                            </div>
                                        </div>
                                    </div>
                            </div>
                        </form>

                    @endforeach

                @endempty
        </div>
    @else
        @include('welcome')
    @endauth
@endsection

<script>

    async function checkPrediction(gameID) {

        const homeTeamScore = document.getElementById('homeTeamScore'+gameID);
        const awayTeamScore = document.getElementById('awayTeamScore'+gameID);
        const prediction_gameID = document.getElementById('prediction_gameID'+gameID);
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Visual validation
        homeTeamScore.style.borderColor = homeTeamScore.value ? "green" : "red";
        awayTeamScore.style.borderColor = awayTeamScore.value ? "green" : "red";

        // Check if both fields are filled (or both empty if that's your requirement)
        const bothFilled = homeTeamScore.value && awayTeamScore.value;
        const bothEmpty = !homeTeamScore.value && !awayTeamScore.value;
        const bothValid = homeTeamScore.value >10 && awayTeamScore.value >10;

        if (bothFilled&&bothValid || bothEmpty) {
            const formData = {
                prediction_gameID: prediction_gameID.value,
                gameID: gameID,
                awayTeamScore: awayTeamScore.value,
                homeTeamScore: homeTeamScore.value
            };

            console.log('Submitting:', formData);

            fetch
            {
                const response = await fetch("{{route('prediction.results')}}", {
                    method: "POST",
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(formData)
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            // Show all error messages
                            showNotification(data.messages.join('\n'));
                        } else {
                            if (bothFilled)
                            showNotification('Spėjimas išsaugotas.', 'success');
                        }
                    });
            }

        }
    }

    // Optional: Add notification function for better UX
    function showNotification(message, type = 'info') {
        // You can use your own notification system here
        // For example, using toast notifications or updating a status element
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px;
            border-radius: 5px;
            color: white;
            z-index: 1000;
            background-color: ${type === 'success' ? '#4CAF50' : '#f44336'};
        `;

        document.body.appendChild(notification);

        // Auto-remove after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
</script>





