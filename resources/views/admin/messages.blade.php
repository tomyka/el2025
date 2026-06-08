@extends('admin.layouts.master')
@section('content')

<div class="sb-card">
    <div class="sb-card-title">
        <i class="bi bi-chat-left-text-fill sb-card-icon"></i> Žinutės
        <span class="badge bg-secondary fw-normal ms-1">{{ $messages->count() }}</span>
    </div>

    @if(Session::has('info'))
    <div class="alert alert-success py-2 mb-3">{{ Session::get('info') }}</div>
    @endif

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 am-table">
            <thead class="table-light">
                <tr>
                    <th class="am-col-id text-muted">#</th>
                    <th class="am-col-msg">Žinutė</th>
                    <th class="am-col-group">Grupė</th>
                    <th class="am-col-active text-center">Aktyvi</th>
                    <th class="am-col-actions"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($messages as $message)
                <tr>
                    <form method="post" action="{{ route('admin.messages') }}">
                    @csrf
                    <input type="hidden" name="messageID" value="{{ $message->id }}">
                    <td class="text-muted am-id">{{ $message->id }}</td>
                    <td>
                        <input type="text" class="form-control form-control-sm"
                               name="message" value="{{ $message->message }}">
                    </td>
                    <td>
                        <select name="groupID" class="form-select form-select-sm am-select">
                            <option value="">— grupė —</option>
                            @foreach($groups as $groupID => $groupName)
                            <option value="{{ $groupID }}" {{ $groupID == $message->group_id ? 'selected' : '' }}>
                                {{ $groupName }}
                            </option>
                            @endforeach
                        </select>
                    </td>
                    <td class="text-center">
                        <div class="form-check form-switch d-flex justify-content-center mb-0">
                            <input type="checkbox" class="form-check-input" role="switch"
                                   name="active" {{ $message->active ? 'checked' : '' }}>
                        </div>
                    </td>
                    <td class="text-end" style="white-space:nowrap;">
                        <button type="submit" name="update" value="1"
                                class="btn btn-sm btn-outline-secondary am-action-btn"
                                title="Išsaugoti">
                            <i class="bi bi-check-lg"></i>
                        </button>
                        @if(session('admin') >= 9)
                        <button type="submit" name="delete" value="1"
                                class="btn btn-sm btn-outline-secondary am-action-btn am-action-delete ms-1"
                                title="Ištrinti"
                                onclick="return confirm('Ištrinti žinutę?')">
                            <i class="bi bi-trash3"></i>
                        </button>
                        @endif
                    </td>
                    </form>
                </tr>
                @endforeach

                {{-- Insert row --}}
                <tr class="am-insert-row">
                    <form method="post" action="{{ route('admin.messageInsert') }}">
                    @csrf
                    <td class="text-muted am-id"><i class="bi bi-plus-lg"></i></td>
                    <td>
                        <input type="text" class="form-control form-control-sm"
                               name="message" placeholder="Nauja žinutė…">
                    </td>
                    <td>
                        <select name="groupID" class="form-select form-select-sm am-select">
                            <option value="">— grupė —</option>
                            @foreach($groups as $groupID => $groupName)
                            <option value="{{ $groupID }}">{{ $groupName }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="text-center">
                        <div class="form-check form-switch d-flex justify-content-center mb-0">
                            <input type="checkbox" class="form-check-input" role="switch" name="active">
                        </div>
                    </td>
                    <td class="text-end">
                        <button type="submit" name="insert" value="1"
                                class="btn btn-sm btn-primary am-action-btn"
                                title="Pridėti žinutę">
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
