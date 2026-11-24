<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BusinessCustomer;
use App\Models\BusinessOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_view_audit_trail_and_admin_is_forbidden(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $superadmin = $this->makeUser(User::ROLE_SUPERADMIN);
        $customer = BusinessCustomer::query()->create(['name' => 'Before update']);

        $this->actingAs($admin);
        $customer->update(['name' => 'After update']);

        $log = AuditLog::query()->where('actor_id', $admin->id)->latest('id')->firstOrFail();
        $this->assertSame('updated', $log->action);
        $this->assertSame([
            'old' => 'Before update',
            'new' => 'After update',
        ], $log->changes['name']);

        $this->get(route('superadmin.audit.index'))->assertForbidden();

        $this->actingAs($superadmin)
            ->get(route('superadmin.audit.index'))
            ->assertOk()
            ->assertSee('Audit trail')
            ->assertSee('After update')
            ->assertSee($admin->name);
    }

    public function test_superadmin_staff_creation_is_audited_without_storing_the_password(): void
    {
        $superadmin = $this->makeUser(User::ROLE_SUPERADMIN);
        $password = 'a-secret-password-for-testing';

        $this->actingAs($superadmin)
            ->post(route('superadmin.admins.store'), [
                'admin_name' => 'New Administrator',
                'admin_email' => 'new-admin@example.com',
                'admin_position' => 'Studio Manager',
                'admin_password' => $password,
                'admin_password_confirmation' => $password,
            ])
            ->assertRedirect(route('superadmin.dashboard'));

        $newAdmin = User::query()->where('email', 'new-admin@example.com')->firstOrFail();
        $log = AuditLog::query()
            ->where('actor_id', $superadmin->id)
            ->where('subject_type', User::class)
            ->where('subject_id', (string) $newAdmin->id)
            ->firstOrFail();

        $this->assertSame('created', $log->action);
        $this->assertSame('[redacted]', $log->changes['password']['new']);
        $this->assertStringNotContainsString($password, json_encode($log->changes));

        $this->actingAs($superadmin)
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee(route('superadmin.audit.index'));
    }

    public function test_changes_without_an_admin_actor_are_not_logged(): void
    {
        BusinessCustomer::query()->create(['name' => 'Publicly created customer']);

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_archiving_an_order_is_recorded_with_its_reason(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $customer = BusinessCustomer::query()->create(['name' => 'Order customer']);
        $order = BusinessOrder::query()->create([
            'order_number' => 'AL-TEST-AUDIT',
            'customer_id' => $customer->id,
            'item_name' => 'Archived test order',
            'description' => 'Order used to test audit records.',
            'order_date' => today(),
            'total_price' => 100,
            'status' => 'new_order',
        ]);

        $this->actingAs($admin);
        $order->update([
            'deletion_reason' => 'Created for testing.',
            'deleted_by' => $admin->id,
        ]);
        $order->delete();

        $log = AuditLog::query()
            ->where('subject_type', BusinessOrder::class)
            ->where('subject_id', (string) $order->id)
            ->where('action', 'deleted')
            ->firstOrFail();

        $this->assertSame('Created for testing.', $log->changes['deletion_reason']['old']);
        $this->assertSame('Archived test order', $log->changes['item_name']['old']);
    }

    private function makeUser(string $role): User
    {
        $user = new User;
        $user->forceFill([
            'name' => ucfirst($role).' User',
            'email' => $role.'-'.uniqid().'@example.com',
            'password' => 'password',
            'role' => $role,
        ])->save();

        return $user;
    }
}
