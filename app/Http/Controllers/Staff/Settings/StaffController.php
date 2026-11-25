<?php

namespace App\Http\Controllers\Staff\Settings;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\StaffUser;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        return view('staff.settings.staff', [
            'members' => StaffUser::with('office')->orderBy('office_id')->orderBy('given_name')->get(),
            'offices' => Office::active()->orderBy('name')->get(),
            'isAdmin' => $request->user('staff')->isAdmin(),
        ]);
    }

    public function store(Request $request)
    {
        $this->adminOnly($request);
        $data = $request->validate([
            'office_id' => ['required', 'exists:offices,id'],
            'given_name' => ['required', 'string', 'max:60'],
            'family_name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'unique:staff_users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(['admin', 'manager', 'teller'])],
            'password' => ['required', 'string', 'min:8'],
        ]);
        StaffUser::create($data + ['is_active' => true]);

        return back()->with('success', 'Staff account created.');
    }

    public function update(Request $request, StaffUser $staffUser)
    {
        $this->adminOnly($request);
        $data = $request->validate([
            'office_id' => ['required', 'exists:offices,id'],
            'role' => ['required', Rule::in(['admin', 'manager', 'teller'])],
            'password' => ['nullable', 'string', 'min:8'],
        ]);
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }
        $data['is_active'] = $request->boolean('is_active');
        abort_if($staffUser->id === $request->user('staff')->id && ! $data['is_active'], 422, "You can't deactivate your own account.");
        $staffUser->update($data);

        return back()->with('success', "{$staffUser->fullName()} updated.");
    }

    private function adminOnly(Request $request): void
    {
        abort_unless($request->user('staff')->isAdmin(), 403, 'Only admins can manage staff accounts.');
    }
}
