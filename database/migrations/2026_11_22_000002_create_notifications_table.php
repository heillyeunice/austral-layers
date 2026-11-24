<?php

use App\Models\User;
use App\Notifications\NewQuoteInquiry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
        });

        $staff = DB::table('users')
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPERADMIN])
            ->get(['id']);
        $inquiries = DB::table('quote_inquiries')
            ->get(['id', 'name', 'service']);
        $timestamp = now();

        foreach ($inquiries as $inquiry) {
            foreach ($staff as $user) {
                DB::table('notifications')->insert([
                    'id' => (string) Str::uuid(),
                    'type' => NewQuoteInquiry::class,
                    'notifiable_type' => User::class,
                    'notifiable_id' => $user->id,
                    'data' => json_encode([
                        'inquiry_id' => $inquiry->id,
                        'customer_name' => $inquiry->name,
                        'service' => $inquiry->service,
                    ], JSON_THROW_ON_ERROR),
                    'read_at' => null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
