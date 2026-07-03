@extends('admin.layouts.master')
@section('content')

<div class="sb-card">
    <div class="sb-card-title">
        <i class="bi bi-calendar-event-fill sb-card-icon"></i> {{ __('Turai') }}
        <span class="badge bg-secondary fw-normal ms-1">{{ $events->count() }}</span>
    </div>

    @if(Session::has('info'))
    <div class="alert alert-success py-2 mb-3">{{ Session::get('info') }}</div>
    @endif

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 ae-table">
            <thead class="table-light">
                <tr>
                    <th class="ae-col-id text-muted">#</th>
                    <th class="ae-col-name">{{ __('Turas') }}</th>
                    <th class="ae-col-short text-center" title="{{ __('Turo diena') }}">{{ __('Diena') }}</th>
                    <th class="ae-col-switch text-center" title="{{ __('Išlikimo turas') }}">{{ __('Išlik.') }}</th>
                    <th class="ae-col-switch text-center" title="{{ __('Play-off turas (baudinių serija)') }}">P/O</th>
                    <th class="ae-col-switch text-center">{{ __('Aktyvus') }}</th>
                    <th class="ae-col-short text-center" title="{{ __('Koeficientas') }}">Rate</th>
                    @if(session('admin') >= 9)
                    <th class="ae-col-actions"></th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($events as $event)
                <tr>
                    <form method="post" action="{{ route('admin.events') }}">
                    @csrf
                    <input type="hidden" name="eventID" value="{{ $event->id }}">
                    <td class="text-muted ae-id">{{ $event->id }}</td>
                    <td>
                        <input type="text" class="form-control form-control-sm"
                               name="event" value="{{ $event->event }}"
                               onchange="this.form.submit()">
                    </td>
                    <td class="text-center">
                        <input type="number" class="form-control form-control-sm text-center ae-tiny-input"
                               name="eventDay" value="{{ $event->event_day }}" min="1"
                               onfocus="this.select()" onblur="this.form.submit()">
                    </td>
                    <td class="text-center">
                        <div class="form-check form-switch d-flex justify-content-center mb-0">
                            <input type="checkbox" class="form-check-input" role="switch"
                                   name="eventSurvival" {{ $event->event_survival ? 'checked' : '' }}
                                   onchange="this.form.submit()">
                        </div>
                    </td>
                    <td class="text-center">
                        <div class="form-check form-switch d-flex justify-content-center mb-0">
                            <input type="checkbox" class="form-check-input" role="switch"
                                   name="isKnockout" {{ $event->is_knockout ? 'checked' : '' }}
                                   onchange="this.form.submit()">
                        </div>
                    </td>
                    <td class="text-center">
                        <div class="form-check form-switch d-flex justify-content-center mb-0">
                            <input type="checkbox" class="form-check-input" role="switch"
                                   name="active" {{ $event->active ? 'checked' : '' }}
                                   onchange="this.form.submit()">
                        </div>
                    </td>
                    <td class="text-center">
                        <input type="number" class="form-control form-control-sm text-center ae-tiny-input"
                               name="rate" value="{{ $event->rate }}" min="0" step="0.01"
                               onfocus="this.select()" onblur="this.form.submit()">
                    </td>
                    @if(session('admin') >= 9)
                    <td class="text-end">
                        <button type="submit" name="delete" value="1"
                                class="btn btn-sm btn-outline-secondary ae-action-btn ae-action-delete"
                                title="{{ __('Ištrinti') }}"
                                onclick="return confirm('{{ __('Ištrinti turą') }} „{{ addslashes($event->event) }}"?')">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </td>
                    @endif
                    </form>
                </tr>
                @endforeach

                {{-- Insert row --}}
                <tr class="ae-insert-row">
                    <form method="post" action="{{ route('admin.eventInsert') }}">
                    @csrf
                    <td class="text-muted ae-id"><i class="bi bi-plus-lg"></i></td>
                    <td>
                        <input type="text" class="form-control form-control-sm"
                               name="event" placeholder="{{ __('Turo pavadinimas…') }}">
                    </td>
                    <td class="text-center">
                        <input type="number" class="form-control form-control-sm text-center ae-tiny-input"
                               name="eventDay" placeholder="1" min="1" onfocus="this.select()">
                    </td>
                    <td class="text-center">
                        <div class="form-check form-switch d-flex justify-content-center mb-0">
                            <input type="checkbox" class="form-check-input" role="switch" name="eventSurvival">
                        </div>
                    </td>
                    <td class="text-center">
                        <div class="form-check form-switch d-flex justify-content-center mb-0">
                            <input type="checkbox" class="form-check-input" role="switch" name="isKnockout">
                        </div>
                    </td>
                    <td class="text-center">
                        <div class="form-check form-switch d-flex justify-content-center mb-0">
                            <input type="checkbox" class="form-check-input" role="switch" name="active">
                        </div>
                    </td>
                    <td class="text-center">
                        <input type="number" class="form-control form-control-sm text-center ae-tiny-input"
                               name="rate" placeholder="1" min="0" step="0.01" onfocus="this.select()">
                    </td>
                    <td class="text-end">
                        <button type="submit" name="insert" value="1"
                                class="btn btn-sm btn-primary ae-action-btn"
                                title="{{ __('Pridėti turą') }}">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </td>
                    </form>
                </tr>
            </tbody>
        </table>
    </div>
</div>

@endsection
