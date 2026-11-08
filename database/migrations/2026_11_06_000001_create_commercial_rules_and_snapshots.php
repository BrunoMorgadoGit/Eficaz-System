<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commercial_policies', function (Blueprint $table) {
            $table->id();
            $table->string('commercial_profile', 20)->unique();
            $table->unsignedTinyInteger('discount_percentage')->default(0);
            $table->string('payment_terms', 255)->nullable();
            $table->timestamps();
        });

        DB::table('commercial_policies')->insert([
            ['commercial_profile' => 'STANDARD', 'discount_percentage' => 0, 'payment_terms' => null, 'created_at' => now(), 'updated_at' => now()],
            ['commercial_profile' => 'GOLD', 'discount_percentage' => 5, 'payment_terms' => null, 'created_at' => now(), 'updated_at' => now()],
            ['commercial_profile' => 'PREMIUM', 'discount_percentage' => 10, 'payment_terms' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('commercial_profile', 20)->index();
            $table->unsignedTinyInteger('discount_percentage');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('is_active')->default(false)->index();
            $table->boolean('is_stackable')->default(false);
            $table->timestamps();

            $table->index(['commercial_profile', 'is_active', 'starts_on', 'ends_on']);
        });

        foreach (['quotes', 'orders'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('commercial_profile', 20)->nullable();
                $table->unsignedTinyInteger('profile_discount_percentage')->nullable();
                $table->foreignId('promotion_id')->nullable()->constrained()->nullOnDelete();
                $table->string('promotion_name', 120)->nullable();
                $table->unsignedTinyInteger('promotion_discount_percentage')->nullable();
                $table->boolean('promotion_is_stackable')->nullable();
                $table->string('payment_terms', 255)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['orders', 'quotes'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['promotion_id']);
                $table->dropColumn([
                    'commercial_profile',
                    'profile_discount_percentage',
                    'promotion_id',
                    'promotion_name',
                    'promotion_discount_percentage',
                    'promotion_is_stackable',
                    'payment_terms',
                ]);
            });
        }

        Schema::dropIfExists('promotions');
        Schema::dropIfExists('commercial_policies');
    }
};
