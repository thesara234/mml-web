<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sender / receiver offices and stores (NZ, Tonga today; more countries later)
        Schema::create('offices', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->comment('Short code e.g. AKL-OTA, TBU-KIN');
            $table->string('name');
            $table->enum('office_type', ['sender', 'receiver', 'both'])->default('both');
            $table->string('country');
            $table->string('country_code', 2)->comment('ISO 3166-1 alpha-2, e.g. NZ, TO');
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('opening_hours')->nullable();
            $table->string('currency', 3)->comment('Local currency, e.g. NZD, TOP');
            $table->boolean('has_store')->default(false)->comment('Office also hands out goods / shopping');
            $table->string('bank_name')->nullable()->comment('For internet-banking deposits to a sender office');
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('staff_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained('offices');
            $table->string('given_name');
            $table->string('family_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password');
            $table->enum('role', ['admin', 'manager', 'teller'])->default('teller');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_users');
        Schema::dropIfExists('offices');
    }
};
