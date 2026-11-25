<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\StaffUser;
use App\Models\StockItem;
use App\Models\Transaction;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StaffLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private StaffUser $teller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->teller = StaffUser::where('email', 'teller.nz@mmltonga.com')->first();
    }

    public function test_counter_send_shopping_paid_now_then_payout_reduces_stock(): void
    {
        $sione = Customer::where('email', 'sione.taufa@example.com')->first();
        $mele = Customer::where('email', 'mele.taufa@example.com')->first();
        $rice = StockItem::where('name', 'Rice 20kg bag')->whereHas('office', fn ($q) => $q->where('code', 'TBU-KIN'))->first();
        $before = $rice->quantity_on_hand;

        $res = $this->actingAs($this->teller, 'staff')->postJson('/staff/send', [
            'sender_customer_id' => $sione->id, 'transfer_type' => 'goods', 'amount' => 150, 'local_currency' => 'NZD', 'foreign_currency' => 'TOP',
            'receiver_customer_id' => $mele->id, 'receiver_office_id' => $rice->office_id, 'items' => [['stock_item_id' => $rice->id, 'quantity' => 1]],
            'purpose' => 'Family support', 'sender_income_source' => 'Salary / wages', 'payment_method' => 'cash', 'paid_now' => true, 'discount' => 5,
        ])->assertOk()->json();

        $t = Transaction::where('reference_no', $res['reference_no'])->first();
        $this->assertSame('ready_for_payout', $t->status);
        $this->assertEquals(5, $t->discount);
        $this->assertEquals(150 + 10 - 5, $t->total_payable);

        $store = StaffUser::where('email', 'store.tonga@mmltonga.com')->first();
        $this->actingAs($store, 'staff')->post("/staff/payout/{$t->id}", ['payout_code' => $t->payout_code, 'id_checked' => '1'])->assertSessionHasNoErrors();
        $this->assertSame($before - 1, $rice->fresh()->quantity_on_hand);
        $this->assertSame('completed', $t->fresh()->status);
    }

    public function test_payout_only_at_the_receiving_office(): void
    {
        $t = Transaction::where('status', 'ready_for_payout')->first();
        $this->actingAs($this->teller, 'staff')->post("/staff/payout/{$t->id}", ['payout_code' => $t->payout_code, 'id_checked' => '1'])
            ->assertSessionHasErrors('status');
    }

    public function test_hold_release_cancel_refund_rules(): void
    {
        $awaiting = Transaction::where('status', 'awaiting_payment')->first();
        $this->actingAs($this->teller, 'staff')->post("/staff/transactions/{$awaiting->id}/hold", ['reason' => 'Checking ID'])->assertSessionHasNoErrors();
        $this->assertSame('on_hold', $awaiting->fresh()->status);
        $this->post("/staff/transactions/{$awaiting->id}/release")->assertSessionHasNoErrors();
        $this->assertSame('awaiting_payment', $awaiting->fresh()->status);

        // unpaid: refund refused, cancel allowed
        $this->post("/staff/transactions/{$awaiting->id}/refund", ['reason' => 'x'])->assertSessionHasErrors('status');
        $this->post("/staff/transactions/{$awaiting->id}/cancel", ['reason' => 'Customer asked'])->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $awaiting->fresh()->status);

        // paid: cancel refused, refund allowed
        $ready = Transaction::where('status', 'ready_for_payout')->first();
        $this->post("/staff/transactions/{$ready->id}/cancel", ['reason' => 'x'])->assertSessionHasErrors('status');
        $this->post("/staff/transactions/{$ready->id}/refund", ['reason' => 'Receiver overseas'])->assertSessionHasNoErrors();
        $this->assertSame('refunded', $ready->fresh()->status);
        $this->assertSame('refunded', $ready->fresh()->payment_status);
    }

    public function test_staff_register_customer_with_passport_photo(): void
    {
        Storage::fake('public');
        $this->actingAs($this->teller, 'staff')->post('/staff/customers', [
            'given_name' => 'Taniela', 'family_name' => 'Moala', 'date_of_birth' => '1992-02-02', 'gender' => 'male',
            'residential_address' => '1 Station Rd', 'city' => 'Auckland', 'country' => 'New Zealand', 'phone' => '+64210000999',
            'passport_photo' => UploadedFile::fake()->image('me.jpg', 350, 450),
            'password' => 'secret123', 'password_confirmation' => 'secret123', 'app_access_enabled' => '1',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $c = Customer::where('phone', '+64210000999')->first();
        $this->assertNotNull($c->passport_photo_path);
        Storage::disk('public')->assertExists($c->passport_photo_path);
        $this->postJson('/api/v1/login', ['login' => '+64210000999', 'password' => 'secret123'])->assertOk();
    }

    public function test_photo_is_required_for_new_customers(): void
    {
        $this->actingAs($this->teller, 'staff')->post('/staff/customers', [
            'given_name' => 'No', 'family_name' => 'Photo', 'date_of_birth' => '1990-01-01', 'gender' => 'female',
            'residential_address' => 'x', 'country' => 'Tonga',
        ])->assertSessionHasErrors('passport_photo');
    }

    public function test_tellers_cannot_change_settings(): void
    {
        $this->actingAs($this->teller, 'staff')->get('/staff/settings/rates')->assertForbidden();
        $admin = StaffUser::where('email', 'admin@mmltonga.com')->first();
        $this->actingAs($admin, 'staff')->post('/staff/settings/rates', ['local_currency' => 'NZD', 'foreign_currency' => 'TOP', 'rate' => 1.4, 'effective_date' => now()->toDateString(), 'with_inverse' => 1])
            ->assertSessionHasNoErrors();
        $this->assertEquals(1.4, \App\Models\ExchangeRate::current('NZD', 'TOP')->rate);
        $this->assertEqualsWithDelta(0.714286, \App\Models\ExchangeRate::current('TOP', 'NZD')->rate, 0.000001);
    }
}
