@extends('layouts.master')
@section('content')
    <canvas id="line-chart" width="600" height="280"></canvas>
    <script>
        new Chart(document.getElementById("line-chart"), {
            type: 'line',
            data: {
                labels: {{$labels}},
                datasets: {!! $datasets !!}
            },
            options: {
                responsive: true,
                hover: {
                    mode: 'nearest',
                    intersect: true
                },
                tooltips: {
                    mode: 'point',
                    intersect: false,
                },
                title: {
                    display: true,
                    text: 'Euroleague 2025/26 Chart'
                },
                scales: {
                    xAxes: [{
                        display: true,
                        scaleLabel: {
                            display: true,
                            labelString: 'Rungtynės'
                        }
                    }],
                    yAxes: [{
                        display: true,
                        scaleLabel: {
                            display: true,
                            labelString: 'Taškai'
                        }
                    }]
                }

            }
        });
    </script>
    </div>
@endsection
