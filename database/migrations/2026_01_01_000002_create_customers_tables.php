<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code', 20)->unique()->comment('e.g. MML-000123');
            $table->string('given_name');
            $table->string('family_name');
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('residential_address');
            $table->string('city')->nullable();
            $table->string('country');
            $table->string('phone')->nullable()->unique();
            $table->string('email')->nullable()->unique();
            $table->string('passport_photo_path')->nullable()->comment('Passport-style photo used to identify the customer at the counter');
            $table->enum('id_type', ['passport', 'drivers_licence', 'national_id', 'other'])->nullable();
            $table->string('id_number')->nullable();
            $table->date('id_expiry')->nullable();

            // Mobile / web login (optional — receivers may never log in)
            $table->string('password')->nullable();
            $table->boolean('app_access_enabled')->default(true);

            // Business rule: new customers are created on the web app only,
            // either by a sender office (staff) or by an existing sender customer.
            $table->foreignId('created_by_office_id')->nullable()->constrained('offices')->nullOnDelete();
            $table->foreignId('registered_by_staff_id')->nullable()->constrained('staff_users')->nullOnDelete();
            $table->foreignId('registered_by_customer_id')->nullable()->constrained('customers')->nullOnDelete();

            $table->boolean('is_verified')->default(false)->comment('KYC checked by staff');
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by_staff_id')->nullable()->constrained('staff_users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();

            $table->index(['family_name', 'given_name']);
        });

        // A sender's saved receivers ("select receiver already in the system")
        Schema::create('customer_receivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('receiver_customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('relationship')->nullable()->comment('e.g. Mother, Brother, Friend');
            $table->foreignId('preferred_office_id')->nullable()->constrained('offices')->nullOnDelete();
            $table->timestamps();

            $table->unique(['sender_customer_id', 'receiver_customer_id'], 'customer_receivers_pair_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_receivers');
        Schema::dropIfExists('customers');
    }
};
