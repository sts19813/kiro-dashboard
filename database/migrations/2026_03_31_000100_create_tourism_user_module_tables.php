<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('tourism_users')) {
            Schema::create('tourism_users', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('phone', 30)->unique();
                $table->string('name', 150)->nullable();
                $table->string('email')->nullable();
                $table->string('preferred_language', 10)->nullable();
                $table->string('currency_code', 3)->default('MXN');
                $table->decimal('budget_min', 10, 2)->nullable();
                $table->decimal('budget_max', 10, 2)->nullable();
                $table->json('preferences')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['is_active', 'phone'], 'tu_active_phone_idx');
            });
        }

        if (! Schema::hasTable('tourism_user_location_histories')) {
            Schema::create('tourism_user_location_histories', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('tourism_user_id');
                $table->decimal('lat', 10, 7);
                $table->decimal('lng', 10, 7);
                $table->decimal('accuracy_meters', 8, 2)->nullable();
                $table->decimal('budget', 10, 2)->nullable();
                $table->string('search_query')->nullable();
                $table->json('context')->nullable();
                $table->timestamp('recorded_at')->nullable();
                $table->timestamps();

                $table->foreign('tourism_user_id', 'tulh_user_fk')
                    ->references('id')
                    ->on('tourism_users')
                    ->cascadeOnDelete();

                $table->index(['tourism_user_id', 'recorded_at'], 'tulh_user_recorded_idx');
            });
        }

        if (! Schema::hasTable('tourism_user_chat_messages')) {
            Schema::create('tourism_user_chat_messages', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('tourism_user_id');
                $table->enum('role', ['user', 'assistant', 'system'])->default('user');
                $table->text('message');
                $table->json('metadata')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();

                $table->foreign('tourism_user_id', 'tucm_user_fk')
                    ->references('id')
                    ->on('tourism_users')
                    ->cascadeOnDelete();

                $table->index(['tourism_user_id', 'sent_at'], 'tucm_user_sent_idx');
            });
        }

        $this->ensureMissingConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tourism_user_chat_messages');
        Schema::dropIfExists('tourism_user_location_histories');
        Schema::dropIfExists('tourism_users');
    }

    private function ensureMissingConstraints(): void
    {
        if (Schema::hasTable('tourism_users') && ! Schema::hasIndex('tourism_users', ['is_active', 'phone'])) {
            Schema::table('tourism_users', function (Blueprint $table) {
                $table->index(['is_active', 'phone'], 'tu_active_phone_idx');
            });
        }

        if (Schema::hasTable('tourism_user_location_histories')) {
            if (! Schema::hasIndex('tourism_user_location_histories', ['tourism_user_id', 'recorded_at'])) {
                Schema::table('tourism_user_location_histories', function (Blueprint $table) {
                    $table->index(['tourism_user_id', 'recorded_at'], 'tulh_user_recorded_idx');
                });
            }

            if (! $this->hasForeignKeyOnColumn('tourism_user_location_histories', 'tourism_user_id')) {
                Schema::table('tourism_user_location_histories', function (Blueprint $table) {
                    $table->foreign('tourism_user_id', 'tulh_user_fk')
                        ->references('id')
                        ->on('tourism_users')
                        ->cascadeOnDelete();
                });
            }
        }

        if (Schema::hasTable('tourism_user_chat_messages')) {
            if (! Schema::hasIndex('tourism_user_chat_messages', ['tourism_user_id', 'sent_at'])) {
                Schema::table('tourism_user_chat_messages', function (Blueprint $table) {
                    $table->index(['tourism_user_id', 'sent_at'], 'tucm_user_sent_idx');
                });
            }

            if (! $this->hasForeignKeyOnColumn('tourism_user_chat_messages', 'tourism_user_id')) {
                Schema::table('tourism_user_chat_messages', function (Blueprint $table) {
                    $table->foreign('tourism_user_id', 'tucm_user_fk')
                        ->references('id')
                        ->on('tourism_users')
                        ->cascadeOnDelete();
                });
            }
        }
    }

    private function hasForeignKeyOnColumn(string $table, string $column): bool
    {
        foreach (Schema::getForeignKeys($table) as $foreignKey) {
            if (($foreignKey['columns'] ?? []) === [$column]) {
                return true;
            }
        }

        return false;
    }
};
