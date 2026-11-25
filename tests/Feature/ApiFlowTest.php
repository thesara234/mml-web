<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Office;
use App\Models\StaffUser;
use App\Models\StockItem;
use App\Models\Transaction;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The contract the Android app relies on. */
class ApiFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function token(string $login = 'sione.taufa@example.com'): string
    {
        return $this->postJson('/api/v1/login', ['login' => $login, 'password' => 'password'])
            ->assertOk()->assertJsonStructure(['token', 'customer' => ['id', 'full_name', 'customer_code']])
            ->json('token');
    }

    public function test_login_by_email_or_phone_and_rejects_bad_password(): void
    {
        $this->postJson('/api/v1/login', ['login' => '+64211234567', 'password' => 'password'])->assertOk();
        $this->postJson('/api/v1/login', ['login' => 'sione.taufa@example.com', 'password' => 'nope'])->assertStatus(422);
        $this->getJson('/api/v1/me')->assertStatus(401);
    }

    public function test_there_is_no_self_registration_endpoint(): void
    {
        $this->postJson('/api/v1/register', [])->assertNotFound();
    }

    public function test_bootstrap_and_quote(): void
    {
        $h = ['Authorization' => 'Bearer '.$this->token()];
        $this->getJson('/api/v1/bootstrap', $h)->assertOk()
            ->assertJsonStructure(['customer', 'corridors', 'sending_offices', 'receiving_offices', 'purposes', 'income_sources', 'payment_methods']);

        // NZD 200 → TOP at 1.36, fee band 100.01–500 = NZD 10 flat
        $this->postJson('/api/v1/quote', ['amount' => 200, 'local_currency' => 'NZD', 'foreign_currency' => 'TOP'], $h)
            ->assertOk()->assertJson(['transfer_amount' => 200, 'converted_amount' => 272, 'service_fee' => 10, 'total_payable' => 210]);

        // "They receive TOP 272" works the other way round
        $this->postJson('/api/v1/quote', ['amount' => 272, 'local_currency' => 'NZD', 'foreign_currency' => 'TOP', 'amount_mode' => 'receive'], $h)
            ->assertOk()->assertJson(['transfer_amount' => 200, 'converted_amount' => 272]);

        // customers can't give themselves a discount
        $this->postJson('/api/v1/quote', ['amount' => 200, 'local_currency' => 'NZD', 'foreign_currency' => 'TOP', 'discount' => 10], $h)
            ->assertJson(['discount' => 0]);
    }

    public function test_save_for_later_then_send_money(): void
    {
        $h = ['Authorization' => 'Bearer '.$this->token()];
        $mele = Customer::where('email', 'mele.taufa@example.com')->first();

        $draft = $this->postJson('/api/v1/transactions', [
            'action' => 'draft', 'transfer_type' => 'money', 'amount' => 150, 'local_currency' => 'NZD', 'foreign_currency' => 'TOP',
        ], $h)->assertCreated()->assertJson(['status' => 'draft'])->json();

        $this->getJson('/api/v1/transactions?box=drafts', $h)->assertOk()->assertJsonFragment(['id' => $draft['id']]);

        // sending without the required details is refused with field errors
        $this->putJson("/api/v1/transactions/{$draft['id']}", ['action' => 'send', 'transfer_type' => 'money', 'amount' => 150, 'local_currency' => 'NZD', 'foreign_currency' => 'TOP'], $h)
            ->assertStatus(422)->assertJsonValidationErrors(['receiver_customer_id', 'purpose', 'sender_income_source', 'payment_method']);

        $sent = $this->putJson("/api/v1/transactions/{$draft['id']}", [
            'action' => 'send', 'transfer_type' => 'money', 'amount' => 150, 'local_currency' => 'NZD', 'foreign_currency' => 'TOP',
            'receiver_customer_id' => $mele->id, 'purpose' => 'Family support', 'sender_income_source' => 'Salary / wages', 'payment_method' => 'internet_banking',
        ], $h)->assertOk()->assertJson(['status' => 'awaiting_payment'])->json();

        $this->assertNotNull($sent['payment_instructions']['account_number']);
        $this->assertSame($sent['reference_no'], $sent['payment_instructions']['reference']);
    }

    public function test_shopping_must_fit_the_amount_and_belong_to_the_store(): void
    {
        $h = ['Authorization' => 'Bearer '.$this->token()];
        $mele = Customer::where('email', 'mele.taufa@example.com')->first();
        $store = Office::where('code', 'TBU-KIN')->first();
        $rice = StockItem::where('office_id', $store->id)->where('name', 'Rice 20kg bag')->first();
        $vavauItem = StockItem::where('office_id', Office::where('code', 'VAV-NEI')->value('id'))->first();

        $base = ['action' => 'send', 'transfer_type' => 'goods', 'local_currency' => 'NZD', 'foreign_currency' => 'TOP',
            'receiver_customer_id' => $mele->id, 'receiver_office_id' => $store->id, 'purpose' => 'Family support',
            'sender_income_source' => 'Salary / wages', 'payment_method' => 'cash'];

        $this->postJson('/api/v1/transactions', $base + ['amount' => 20, 'items' => [['stock_item_id' => $rice->id, 'quantity' => 2]]], $h)
            ->assertStatus(422)->assertJsonValidationErrors('items');
        $this->postJson('/api/v1/transactions', $base + ['amount' => 200, 'items' => [['stock_item_id' => $vavauItem->id, 'quantity' => 1]]], $h)
            ->assertStatus(422)->assertJsonValidationErrors('items');

        $t = $this->postJson('/api/v1/transactions', $base + ['amount' => 200, 'items' => [['stock_item_id' => $rice->id, 'quantity' => 2]]], $h)
            ->assertCreated()->json();
        $this->assertEquals(136, $t['goods_total']);
        $this->assertEquals(136, $t['cash_payout']); // 272 − 136
    }

    public function test_receiver_sees_code_only_when_ready_and_staff_pay_out(): void
    {
        $sender = Customer::where('email', 'sione.taufa@example.com')->first();
        $mele = Customer::where('email', 'mele.taufa@example.com')->first();
        $hs = ['Authorization' => 'Bearer '.$this->token()];
        $hr = ['Authorization' => 'Bearer '.$this->token('mele.taufa@example.com')];

        $t = $this->postJson('/api/v1/transactions', [
            'action' => 'send', 'transfer_type' => 'money', 'amount' => 100, 'local_currency' => 'NZD', 'foreign_currency' => 'TOP',
            'receiver_customer_id' => $mele->id, 'purpose' => 'Family support', 'sender_income_source' => 'Salary / wages', 'payment_method' => 'internet_banking',
        ], $hs)->json();

        $this->app['auth']->forgetGuards(); // switch from the sender's token to the receiver's
        $incoming = collect($this->getJson('/api/v1/incoming?type=money', $hr)->assertOk()->json())->firstWhere('id', $t['id']);
        $this->assertNull($incoming['payout_code']);

        // Otahuhu teller confirms the bank deposit
        $teller = StaffUser::where('email', 'teller.nz@mmltonga.com')->first();
        $this->actingAs($teller, 'staff')->post("/staff/transactions/{$t['id']}/confirm-payment")->assertRedirect();

        $this->app['auth']->forgetGuards();
        $incoming = collect($this->getJson('/api/v1/incoming?type=money', $hr)->json())->firstWhere('id', $t['id']);
        $this->assertSame('ready_for_payout', $incoming['status']);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $incoming['payout_code']);

        // Wrong code is refused, right code completes
        $store = StaffUser::where('email', 'store.tonga@mmltonga.com')->first();
        $this->actingAs($store, 'staff')->post("/staff/payout/{$t['id']}", ['payout_code' => '000000x', 'id_checked' => '1'])->assertSessionHasErrors('payout_code');
        $this->actingAs($store, 'staff')->post("/staff/payout/{$t['id']}", ['payout_code' => $incoming['payout_code'], 'id_checked' => '1'])->assertSessionHasNoErrors();
        $this->assertSame('completed', Transaction::find($t['id'])->status);
    }

    public function test_saved_receivers_include_personal_details_but_search_does_not(): void
    {
        $h = ['Authorization' => 'Bearer '.$this->token()];
        $mele = collect($this->getJson('/api/v1/receivers', $h)->assertOk()->json())->firstWhere('full_name', 'Mele Taufa');
        $this->assertSame("Ma'ufanga", $mele['residential_address']);
        $this->assertSame('1958-09-03', $mele['date_of_birth']);
        $this->assertSame('Mother', $mele['relationship']);

        $found = $this->getJson('/api/v1/receivers/search?q=Losana', $h)->assertOk()->json();
        $this->assertArrayNotHasKey('residential_address', $found[0]);
        $this->assertStringContainsString('•', $found[0]['phone']); // masked
    }

    public function test_customer_cannot_see_someone_elses_transfer(): void
    {
        $other = Transaction::where('sender_customer_id', Customer::where('email', 'ana.fifita@example.com')->value('id'))->where('status', '!=', 'draft')->first();
        $this->getJson("/api/v1/transactions/{$other->id}", ['Authorization' => 'Bearer '.$this->token()])->assertForbidden();
    }

    public function test_chat_round_trip(): void
    {
        $h = ['Authorization' => 'Bearer '.$this->token()];
        $this->postJson('/api/v1/chat/messages', ['body' => 'Mālō, is Kinikinilau open Saturday?'], $h)->assertCreated();
        $msgs = $this->getJson('/api/v1/chat/messages', $h)->assertOk()->json('messages');
        $this->assertSame('Mālō, is Kinikinilau open Saturday?', end($msgs)['body']);
    }

    public function test_staff_can_start_a_chat_and_customer_sees_it_as_unread(): void
    {
        $admin = StaffUser::where('email', 'admin@mmltonga.com')->firstOrFail();
        $mele = Customer::where('email', 'mele.taufa@example.com')->firstOrFail();

        // staff open a chat from the customer's profile and write first
        $this->actingAs($admin, 'staff')->post("/staff/customers/{$mele->id}/chat")->assertRedirect();
        $conv = \App\Models\Conversation::where('customer_id', $mele->id)->firstOrFail();
        $this->actingAs($admin, 'staff')->postJson("/staff/api/messages/{$conv->id}", ['body' => 'Your shopping is ready at Kinikinilau.'])->assertCreated();

        $h = ['Authorization' => 'Bearer '.$this->token('mele.taufa@example.com')];
        $this->assertSame(1, $this->getJson('/api/v1/me', $h)->assertOk()->json('unread_messages'));

        // reading the chat clears the badge
        $msgs = $this->getJson('/api/v1/chat/messages', $h)->assertOk()->json('messages');
        $this->assertSame('Your shopping is ready at Kinikinilau.', end($msgs)['body']);
        $this->assertSame(0, $this->getJson('/api/v1/bootstrap', $h)->json('customer.unread_messages'));
    }
}
