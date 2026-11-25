@extends('layouts.staff')
@section('title', 'Staff accounts')
@section('content')
<div class="page-head">
    <div><h1>Staff accounts</h1><p>Tellers process transfers. Managers also change rates, fees, stock and offices. Admins manage staff accounts.</p></div>
    @if ($isAdmin)<button class="btn btn-primary" data-open-dialog="dlg-staff-new"><i class="icon-user-plus"></i>Add staff</button>@endif
</div>
<div class="card"><div class="table-wrap"><table class="table">
    <thead><tr><th>Name</th><th>Office</th><th>Role</th><th>Last sign in</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @foreach ($members as $m)
        <tr>
            <td><div class="person"><span class="avatar avatar-sm">{{ $m->initials() }}</span><div class="meta"><strong>{{ $m->fullName() }}</strong><small>{{ $m->email }}</small></div></div></td>
            <td>{{ $m->office->name }}</td>
            <td><span class="tag">{{ ucfirst($m->role) }}</span></td>
            <td class="muted small">{{ $m->last_login_at?->diffForHumans() ?? 'Never' }}</td>
            <td>@if($m->is_active)<span class="badge tone-green">Active</span>@else<span class="badge tone-neutral">Disabled</span>@endif</td>
            <td class="right">@if($isAdmin)<button class="btn btn-sm" data-open-dialog="dlg-staff-{{ $m->id }}"><i class="icon-pencil"></i>Edit</button>@endif</td>
        </tr>
        @if ($isAdmin)
        <dialog class="modal" id="dlg-staff-{{ $m->id }}"><form method="post" action="{{ route('staff.settings.staff.update', $m) }}">@csrf @method('put')
            <div class="modal-body"><h3>{{ $m->fullName() }}</h3>
                <div class="field"><label>Office</label><select class="select" name="office_id">@foreach ($offices as $o)<option value="{{ $o->id }}" @selected($o->id === $m->office_id)>{{ $o->name }}</option>@endforeach</select></div>
                <div class="field"><label>Role</label><select class="select" name="role">@foreach (['teller', 'manager', 'admin'] as $r)<option value="{{ $r }}" @selected($m->role === $r)>{{ ucfirst($r) }}</option>@endforeach</select></div>
                <div class="field"><label>New password</label><input class="input" type="password" name="password" minlength="8" placeholder="Leave blank to keep"></div>
                <label class="check" style="margin-bottom:1rem"><input type="checkbox" name="is_active" value="1" @checked($m->is_active)> Can sign in</label>
            </div>
            <div class="card-foot"><button type="button" class="btn btn-ghost" data-close-dialog>Close</button><button class="btn btn-primary">Save</button></div>
        </form></dialog>
        @endif
    @endforeach
    </tbody>
</table></div></div>
@if ($isAdmin)
<dialog class="modal" id="dlg-staff-new"><form method="post" action="{{ route('staff.settings.staff.store') }}">@csrf
    <div class="modal-body"><h3>Add staff</h3>
        <div class="form-grid">
            <div class="field"><label>Given name</label><input class="input" name="given_name" required></div>
            <div class="field"><label>Family name</label><input class="input" name="family_name" required></div>
            <div class="field span-2"><label>Email</label><input class="input" type="email" name="email" required></div>
            <div class="field"><label>Office</label><select class="select" name="office_id">@foreach ($offices as $o)<option value="{{ $o->id }}">{{ $o->name }}</option>@endforeach</select></div>
            <div class="field"><label>Role</label><select class="select" name="role"><option value="teller">Teller</option><option value="manager">Manager</option><option value="admin">Admin</option></select></div>
            <div class="field span-2"><label>Temporary password</label><input class="input" type="password" name="password" minlength="8" required></div>
        </div>
    </div>
    <div class="card-foot"><button type="button" class="btn btn-ghost" data-close-dialog>Close</button><button class="btn btn-primary">Create account</button></div>
</form></dialog>
@endif
@endsection
