<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no', 30)->unique();
            $table->dateTime('transaction_date');

            // money  = receiver collects cash
            // goods  = receiver collects shopping from the store (any unspent balance is paid in cash)
            $table->enum('transfer_type', ['money', 'goods'])->default('money');

            // Parties. Receiver side is nullable so an incomplete transfer can be "saved for later".
            $table->foreignId('sender_customer_id')->constrained('customers');
            $table->foreignId('sender_office_id')->nullable()->constrained('offices');
            $table->foreignId('receiver_customer_id')->nullable()->constrained('customers');
            $table->foreignId('receiver_office_id')->nullable()->constrained('offices');

            // Money
            $table->string('local_currency', 3)->comment('Currency the sender pays in');
            $table->string('foreign_currency', 3)->comment('Currency the receiver collects in');
            $table->foreignId('exchange_rate_id')->nullable()->constrained('exchange_rates')->nullOnDelete();
            $table->decimal('exchange_rate', 12, 6)->comment('Rate snapshot at time of quote');
            $table->decimal('transfer_amount', 14, 2)->comment('Amount sent, in local currency');
            $table->decimal('converted_amount', 14, 2)->comment('Amount the receiver gets, in foreign currency');
            $table->decimal('service_fee', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total_payable', 14, 2)->comment('transfer_amount + service_fee - discount');
            $table->decimal('goods_total', 14, 2)->default(0)->comment('Shopping total, foreign currency');
            $table->decimal('cash_payout', 14, 2)->default(0)->comment('Cash handed to receiver, foreign currency');

            // Compliance
            $table->string('purpose')->nullable();
            $table->string('sender_income_source')->nullable();

            // How the sender pays
            $table->enum('payment_method', ['internet_banking', 'cash'])->nullable();
            $table->string('payment_reference')->nullable();
            $table->enum('payment_status', ['unpaid', 'paid', 'refunded'])->default('unpaid');
            $table->dateTime('paid_at')->nullable();
            $table->foreignId('payment_confirmed_by_staff_id')->nullable()->constrained('staff_users')->nullOnDelete();

            // Collection
            $table->string('payout_code', 8)->nullable()->comment('Secret code the receiver quotes at the counter');

            $table->enum('status', [
                'draft',            // saved for later by the sender
                'awaiting_payment', // submitted, waiting for internet banking / cash
                'ready_for_payout', // paid; receiver can collect
                'on_hold',          // paused by staff (compliance, query …)
                'completed',        // receiver collected money / shopping
                'cancelled',        // cancelled before payment was taken
                'refunded',         // money returned to the sender
            ])->default('draft');
            $table->string('status_before_hold')->nullable();
            $table->string('hold_reason')->nullable();

            $table->enum('initiated_via', ['mobile_app', 'web_customer', 'web_office'])->default('web_office');
            $table->foreignId('created_by_staff_id')->nullable()->constrained('staff_users')->nullOnDelete();
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('completed_by_staff_id')->nullable()->constrained('staff_users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['sender_customer_id', 'status']);
            $table->index(['receiver_customer_id', 'status']);
        });

        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('stock_item_id')->constrained('stock_items');
            $table->string('name')->comment('Snapshot of item name');
            $table->string('unit')->default('each');
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('line_total', 14, 2);
            $table->timestamps();
        });

        Schema::create('transaction_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('actor_type')->comment('staff, customer or system');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_events');
        Schema::dropIfExists('transaction_items');
        Schema::dropIfExists('transactions');
    }
};
