@extends('layouts.master')
@section('content')

<div class="sb-card mb-4">
    <div class="sb-card-title">
        <i class="bi bi-trophy-fill sb-card-icon" style="color:#f59e0b"></i> {{ __('Lyderių lentelė') }}
    </div>
    <p class="text-muted mb-4" style="font-size:.88rem">
        {{ __('Žaidžiame nuo 2016 metų — kiekvienas turnyras prideda naujų iššūkių ir intrigų.') }}
        <a href="{{ route('leaderboard') }}" style="color:var(--sb-accent)">{{ __('Prisijunk') }}</a> {{ __('ir išbandyk save.') }}
    </p>

    <div class="table-responsive">
        <table class="table align-middle mb-0" style="font-size:.9rem">
            <thead class="table-light">
                <tr>
                    <th style="width:48px">#</th>
                    <th>{{ __('Žaidėjas') }}</th>
                    <th class="text-end">{{ __('Taškai') }}</th>
                    <th class="text-end d-none d-sm-table-cell">{{ __('Tikslūs') }}</th>
                    <th class="text-end d-none d-md-table-cell">{{ __('Nugalėtojai') }}</th>
                    <th class="text-end d-none d-md-table-cell">{{ __('Žaidimai') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($players as $i => $p)
                @php $rank = $i + 1; @endphp
                <tr class="{{ $rank <= 3 ? 'lb-pub-top' : '' }}">
                    <td>
                        @if($rank === 1)
                            <span class="lb-pub-medal lb-pub-gold">🥇</span>
                        @elseif($rank === 2)
                            <span class="lb-pub-medal lb-pub-silver">🥈</span>
                        @elseif($rank === 3)
                            <span class="lb-pub-medal lb-pub-bronze">🥉</span>
                        @else
                            <span class="lb-pub-rank">{{ $rank }}</span>
                        @endif
                    </td>
                    <td class="fw-semibold">{{ $p->username }}</td>
                    <td class="text-end fw-bold" style="color:var(--sb-accent)">{{ number_format($p->total_points, 1) }}</td>
                    <td class="text-end d-none d-sm-table-cell text-muted">{{ $p->exact_predictions }}</td>
                    <td class="text-end d-none d-md-table-cell text-muted">{{ $p->correct_winners }}</td>
                    <td class="text-end d-none d-md-table-cell text-muted">{{ $p->games_predicted }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="sb-charity-card">
    <div class="sb-charity-card-inner">
        <div class="sb-charity-icon">♥</div>
        <div class="sb-charity-body">
            <h3 class="sb-charity-title">{{ __('Žaidžiame dėl gero tikslo') }}</h3>
            <p class="sb-charity-text">
                {{ __('SportBet — tai ne tik futbolo prognozių žaidimas. Nuo 2018 metų žaidėjai savanoriškai aukoja Jaunimo linijai, teikiančiai psichologinę pagalbą jaunimui visoje Lietuvoje.') }}
            </p>
            <a href="{{ route('charity') }}" class="sb-charity-link">
                {{ __('Sužinoti daugiau apie labdarą') }} <i class="bi bi-arrow-right-short"></i>
            </a>
        </div>
        <div class="sb-charity-stat-box">
            <span class="sb-charity-amount">7 500€</span>
            <span class="sb-charity-stat-label">{{ __('paaukota nuo 2018 m.') }}</span>
        </div>
    </div>
</div>

@endsection
