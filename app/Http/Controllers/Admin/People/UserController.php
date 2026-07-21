<?php

namespace App\Http\Controllers\Admin\People;

use App\Models\User;
use App\Modules\User\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    /** Roles an admin may assign from this screen. */
    private const ASSIGNABLE_ROLES = ['admin', 'teacher', 'accountant', 'librarian', 'receptionist', 'student', 'parent'];

    public function __construct(private readonly UserService $users) {}

    public function index(): View
    {
        $users = User::where('school_id', app('current_school_id'))
            ->with('roles:id,name')
            ->orderBy('name')
            ->get();

        return view('admin.people.users.index', [
            'users' => $users,
            'roles' => self::ASSIGNABLE_ROLES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = app('current_school_id');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', 'string', 'in:'.implode(',', self::ASSIGNABLE_ROLES)],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $this->users->createUser($data, $schoolId);

        return back()->with('status', 'User created.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $user = User::where('school_id', app('current_school_id'))->findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $this->users->updateUser($user, $data);

        return back()->with('status', 'User updated.');
    }

    public function changeRole(Request $request, int $id): RedirectResponse
    {
        $user = User::where('school_id', app('current_school_id'))->findOrFail($id);

        $data = $request->validate([
            'role' => ['required', 'string', 'in:'.implode(',', self::ASSIGNABLE_ROLES)],
        ]);

        $this->users->changeRole($user, $data['role']);

        return back()->with('status', 'Role updated.');
    }

    public function deactivate(int $id): RedirectResponse
    {
        $user = User::where('school_id', app('current_school_id'))->findOrFail($id);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $this->users->deactivate($user);

        return back()->with('status', 'User deactivated.');
    }
}
