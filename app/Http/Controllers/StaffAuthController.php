<?php

namespace App\Http\Controllers;

use App\Models\BusinessOrder;
use App\Models\BusinessOrderStageAssignment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class StaffAuthController extends Controller
{
    private const ROLES = [
        'admin' => [
            'label' => 'Admin',
            'dashboard' => 'admin.dashboard',
            'login' => 'admin.login',
            'logout' => 'admin.logout',
        ],
        'superadmin' => [
            'label' => 'SuperAdmin',
            'dashboard' => 'superadmin.dashboard',
            'login' => 'superadmin.login',
            'logout' => 'superadmin.logout',
        ],
    ];

    public function create(string $role): View
    {
        $roleConfig = $this->roleConfig($role);

        if (Auth::check() && Auth::user()->role !== $role) {
            $currentRoleConfig = $this->roleConfig(Auth::user()->role);

            return view('auth.staff-role-switch', [
                'role' => $role,
                'roleLabel' => $roleConfig['label'],
                'currentRoleLabel' => $currentRoleConfig['label'],
                'currentDashboard' => route($currentRoleConfig['dashboard']),
                'noIndex' => true,
            ]);
        }

        return view('auth.staff-login', [
            'role' => $role,
            'roleLabel' => $roleConfig['label'],
            'noIndex' => true,
        ]);
    }

    public function store(Request $request, string $role): RedirectResponse
    {
        $roleConfig = $this->roleConfig($role);
        $validator = Validator::make($request->all(), [
            'login_email' => ['required', 'email', 'max:255'],
            'login_password' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator, 'staffLogin')
                ->withInput($request->only('login_email'));
        }

        $credentials = $validator->validated();

        if (! Auth::attempt([
            'email' => strtolower($credentials['login_email']),
            'password' => $credentials['login_password'],
            'role' => $role,
        ])) {
            return back()
                ->withErrors(['login_email' => 'These credentials do not match our records.'], 'staffLogin')
                ->withInput(['login_email' => $credentials['login_email']]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route($roleConfig['dashboard']));
    }

    public function dashboard(string $role): View
    {
        $roleConfig = $this->roleConfig($role);
        $myTasks = BusinessOrderStageAssignment::query()
            ->where('assigned_staff_id', auth()->id())
            ->whereIn('stage', BusinessOrder::ASSIGNABLE_STAGES)
            ->whereHas('order', function ($query): void {
                $query
                    ->whereColumn('business_orders.status', 'business_order_stage_assignments.stage')
                    ->whereNotIn('business_orders.status', ['completed', 'cancelled']);
            })
            ->with('order.customer')
            ->latest('business_order_stage_assignments.updated_at')
            ->get();

        return view('auth.staff-dashboard', [
            'roleLabel' => $roleConfig['label'],
            'myTasks' => $myTasks,
            'noIndex' => true,
        ]);
    }

    public function nominateAdmin(Request $request): RedirectResponse
    {
        $request->merge([
            'admin_email' => strtolower(trim((string) $request->input('admin_email'))),
        ]);

        $validator = Validator::make($request->all(), [
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_position' => ['required', 'string', 'max:120'],
            'admin_password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator, 'adminNomination')
                ->withInput($request->except(['admin_password', 'admin_password_confirmation']));
        }

        $validated = $validator->validated();
        $admin = new User;
        $admin->name = $validated['admin_name'];
        $admin->email = $validated['admin_email'];
        $admin->position = $validated['admin_position'];
        $admin->password = $validated['admin_password'];
        $admin->role = User::ROLE_ADMIN;
        $admin->save();

        return redirect()
            ->route('superadmin.dashboard')
            ->with('admin_nominated', $admin->name);
    }

    public function destroy(Request $request, string $role): RedirectResponse
    {
        $roleConfig = $this->roleConfig($role);

        $this->endSession($request);

        return redirect()->route($roleConfig['login']);
    }

    public function logoutAndSwitch(Request $request, string $role): RedirectResponse
    {
        $roleConfig = $this->roleConfig($role);

        if ($request->user()->role === $role) {
            return redirect()->route($roleConfig['dashboard']);
        }

        $this->endSession($request);

        return redirect()->route($roleConfig['login']);
    }

    private function endSession(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    private function roleConfig(string $role): array
    {
        return self::ROLES[$role] ?? abort(404);
    }
}
