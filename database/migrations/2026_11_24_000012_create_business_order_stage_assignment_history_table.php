<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_order_stage_assignment_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('business_orders')->cascadeOnDelete();
            $table->string('stage', 40);
            $table->foreignId('assigned_staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('assigned_staff_name')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('changed_by_name')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'stage', 'created_at']);
        });

        $existingAssignments = DB::table('business_order_stage_assignments')
            ->leftJoin('users', 'users.id', '=', 'business_order_stage_assignments.assigned_staff_id')
            ->whereIn('business_order_stage_assignments.stage', [
                'modeling',
                'production',
                'quality_checking',
                'packaging',
                'ready_for_shipping',
                'shipped',
            ])
            ->whereNotNull('business_order_stage_assignments.assigned_staff_id')
            ->get([
                'business_order_stage_assignments.order_id',
                'business_order_stage_assignments.stage',
                'business_order_stage_assignments.assigned_staff_id',
                'users.name as assigned_staff_name',
                'business_order_stage_assignments.created_at',
            ]);

        foreach ($existingAssignments as $assignment) {
            DB::table('business_order_stage_assignment_history')->insert([
                'order_id' => $assignment->order_id,
                'stage' => $assignment->stage,
                'assigned_staff_id' => $assignment->assigned_staff_id,
                'assigned_staff_name' => $assignment->assigned_staff_name,
                'changed_by' => null,
                'changed_by_name' => null,
                'created_at' => $assignment->created_at,
                'updated_at' => $assignment->created_at,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('business_order_stage_assignment_history');
    }
};
