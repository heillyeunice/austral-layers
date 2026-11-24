<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuditTrailController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'actor_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where(
                fn ($query) => $query->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPERADMIN])
            )],
            'action' => ['nullable', Rule::in(['created', 'updated', 'deleted', 'restored'])],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $logs = AuditLog::query()
            ->when($validated['actor_id'] ?? null, fn ($query, $actorId) => $query->where('actor_id', $actorId))
            ->when($validated['action'] ?? null, fn ($query, $action) => $query->where('action', $action))
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('subject_label', 'like', '%'.$search.'%')
                        ->orWhere('actor_name', 'like', '%'.$search.'%')
                        ->orWhere('subject_type', 'like', '%'.$search.'%');
                });
            })
            ->with('actor')
            ->latest('created_at')
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.audit-trail.index', [
            'logs' => $logs,
            'actors' => User::query()
                ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPERADMIN])
                ->orderBy('name')
                ->get(['id', 'name']),
            'filters' => $validated,
        ]);
    }
}
