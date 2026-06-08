@extends('admin.layouts.master')
@section('content')

<div class="sb-card">
    <div class="sb-card-title">
        <i class="bi bi-diagram-3-fill sb-card-icon"></i> Grupės
        <span class="badge bg-secondary fw-normal ms-1">{{ $groups->count() }}</span>
    </div>

    @if(Session::has('info'))
    <div class="alert alert-success py-2 mb-3">{{ Session::get('info') }}</div>
    @endif

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 ag-table">
            <thead class="table-light">
                <tr>
                    <th class="ag-col-id text-muted">#</th>
                    <th class="ag-col-name">Grupė</th>
                    <th class="ag-col-fee text-center" title="Mokestis">Mok.</th>
                    <th class="ag-col-desc">Mokesčio aprašymas</th>
                    <th class="ag-col-ratio text-center" title="Atlygis koeficientas">Atl.</th>
                    <th class="ag-col-desc">Atlygis aprašymas</th>
                    <th class="ag-col-actions"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($groups as $group)
                <tr>
                    <form method="post" action="{{ route('admin.groups') }}">
                    @csrf
                    <input type="hidden" name="groupID" value="{{ $group->id }}">
                    <td class="text-muted ag-id">{{ $group->id }}</td>
                    <td>
                        <input type="text" class="form-control form-control-sm"
                               name="group" value="{{ $group->group }}">
                    </td>
                    <td class="text-center">
                        <input type="number" class="form-control form-control-sm text-center ag-tiny-input"
                               name="fee" value="{{ $group->fee }}" min="0" step="0.01">
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm"
                               name="feeDescription" value="{{ $group->fee_description }}">
                    </td>
                    <td class="text-center">
                        <input type="number" class="form-control form-control-sm text-center ag-tiny-input"
                               name="rewardRatio" value="{{ $group->reward_ratio }}" min="0" step="0.01">
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm"
                               name="rewardDescription" value="{{ $group->reward_description }}">
                    </td>
                    <td class="text-end" style="white-space:nowrap;">
                        <button type="submit" name="update" value="1"
                                class="btn btn-sm btn-outline-secondary ag-action-btn"
                                title="Išsaugoti">
                            <i class="bi bi-check-lg"></i>
                        </button>
                        @if(session('admin') >= 9)
                        <button type="submit" name="delete" value="1"
                                class="btn btn-sm btn-outline-secondary ag-action-btn ag-action-delete ms-1"
                                title="Ištrinti"
                                onclick="return confirm('Ištrinti grupę „{{ addslashes($group->group) }}"?')">
                            <i class="bi bi-trash3"></i>
                        </button>
                        @endif
                    </td>
                    </form>
                </tr>
                @endforeach

                {{-- Insert row --}}
                <tr class="ag-insert-row">
                    <form method="post" action="{{ route('admin.groupInsert') }}">
                    @csrf
                    <td class="text-muted ag-id"><i class="bi bi-plus-lg"></i></td>
                    <td>
                        <input type="text" class="form-control form-control-sm"
                               name="group" placeholder="Pavadinimas">
                    </td>
                    <td class="text-center">
                        <input type="number" class="form-control form-control-sm text-center ag-tiny-input"
                               name="fee" placeholder="0" min="0" step="0.01">
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm"
                               name="feeDescription" placeholder="Mokesčio aprašymas">
                    </td>
                    <td class="text-center">
                        <input type="number" class="form-control form-control-sm text-center ag-tiny-input"
                               name="rewardRatio" placeholder="0" min="0" step="0.01">
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm"
                               name="rewardDescription" placeholder="Atlygis aprašymas">
                    </td>
                    <td class="text-end">
                        <button type="submit" name="insert" value="1"
                                class="btn btn-sm btn-primary ag-action-btn"
                                title="Pridėti grupę">
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
