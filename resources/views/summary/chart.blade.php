@extends('layouts.master')
@section('containerClass', 'sb-container--fluid')
@section('content')

<div class="sb-card p-0">
    <div class="ch-header">
        <span class="ch-title">Taškų dinamika</span>
        <span class="ch-meta">{{ $gameCount }} {{ $gameCount === 1 ? 'rungtynės' : 'rungtynių' }}</span>
    </div>
    <div class="ch-wrap">
        <canvas id="trend-chart"></canvas>
    </div>
</div>

<script>
const gameLabels = {!! $gameLabels !!};

const ctx = document.getElementById('trend-chart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: Array.from({ length: gameLabels.length }, (_, i) => i + 1),
        datasets: {!! $datasets !!}
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'nearest', intersect: true },
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    usePointStyle: true,
                    pointStyle: 'circle',
                    padding: 20,
                    font: { size: 12, family: 'Inter, sans-serif' }
                }
            },
            tooltip: {
                backgroundColor: '#1e293b',
                titleColor: '#94a3b8',
                bodyColor: '#f1f5f9',
                borderColor: '#334155',
                borderWidth: 1,
                padding: 12,
                callbacks: {
                    title: function (items) {
                        return gameLabels[items[0].dataIndex] ?? ('Žaidimas ' + items[0].label);
                    },
                    label: function (item) {
                        return '  ' + item.dataset.label + ':  ' + item.parsed.y.toFixed(1) + ' t.';
                    }
                }
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: {
                    color: '#94a3b8',
                    font: { size: 11 },
                    maxTicksLimit: 20,
                    autoSkip: true
                },
                border: { display: false }
            },
            y: {
                beginAtZero: true,
                grid: { color: '#f1f5f9' },
                ticks: {
                    color: '#94a3b8',
                    font: { size: 11 }
                },
                border: { display: false }
            }
        }
    }
});
</script>

@endsection
