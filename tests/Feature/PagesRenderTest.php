<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\StaffUser;
use App\Models\Transaction;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Smoke test: every screen renders for the people who use it. */
class PagesRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_public_site(): void
    {
        foreach (['/', '/rates', '/shop', '/locations', '/track', '/staff/login', '/portal/login'] as $url) {
            $this->get($url)->assertOk();
        }
        $t = Transaction::where('status', 'ready_for_payout')->with('senderCustomer')->first();
        $this->post('/track', ['reference_no' => $t->reference_no, 'family_name' => $t->senderCustomer->family_name])->assertOk()->assertSee($t->reference_no);
        $this->postJson('/quote', ['amount' => 100, 'local_currency' => 'NZD', 'foreign_currency' => 'TOP'])->assertOk();
    }

    public function test_groceries_page_shows_product_pictures(): void
    {
        $this->get('/shop')->assertOk()
            ->assertSee('images/store-shelf.png', false)
            ->assertSee('images/products/rice-20kg.jpg', false);

        // every seeded picture exists on disk
        foreach (\App\Models\StockItem::whereNotNull('image_path')->pluck('image_path')->unique() as $path) {
            $this->assertFileExists(public_path($path));
        }
    }

    public function test_staff_console(): void
    {
        $admin = StaffUser::where('email', 'admin@mmltonga.com')->first();
        $t = Transaction::where('status', '!=', 'draft')->first();
        $goods = Transaction::where('transfer_type', 'goods')->where('status', '!=', 'draft')->first();
        $c = Customer::first();
        $conv = Conversation::first();

        $urls = ['/staff', '/staff/send', '/staff/send?sender='.$c->id, '/staff/payout', '/staff/payout?q=Taufa', '/staff/payout?t='.$goods->id,
            '/staff/transactions', '/staff/transactions?status=on_hold&q=Taufa', "/staff/transactions/{$t->id}", "/staff/transactions/{$goods->id}",
            "/staff/transactions/{$t->id}/receipt", '/staff/customers', '/staff/customers?kyc=pending', '/staff/customers/create',
            "/staff/customers/create?for_sender={$c->id}", "/staff/customers/{$c->id}", "/staff/customers/{$c->id}/edit",
            '/staff/messages', "/staff/messages/{$conv->id}", '/staff/settings/rates', '/staff/settings/fees', '/staff/settings/stock',
            '/staff/settings/offices', '/staff/settings/staff'];
        foreach ($urls as $url) {
            $this->actingAs($admin, 'staff')->get($url)->assertOk();
        }
        $this->getJson('/staff/api/customers/search?q=Tau')->assertOk()->assertJsonFragment(['family_name' => 'Taufa']);
        $this->getJson("/staff/api/messages/{$conv->id}")->assertOk();
    }

    public function test_customer_portal(): void
    {
        $sione = Customer::where('email', 'sione.taufa@example.com')->first();
        $t = Transaction::where('sender_customer_id', $sione->id)->where('status', '!=', 'draft')->first();
        $draft = Transaction::where('sender_customer_id', $sione->id)->where('status', 'draft')->first();
        foreach (['/portal', '/portal/send', "/portal/send?draft={$draft->id}", '/portal/receivers', '/portal/receivers/create',
            '/portal/transfers', '/portal/transfers?tab=incoming', '/portal/transfers?tab=drafts', "/portal/transfers/{$t->id}", '/portal/chat', '/portal/profile'] as $url) {
            $this->actingAs($sione, 'customer')->get($url)->assertOk();
        }
        $mele = Customer::where('email', 'mele.taufa@example.com')->first();
        $incoming = Transaction::where('receiver_customer_id', $mele->id)->where('status', 'ready_for_payout')->first();
        $this->actingAs($mele, 'customer')->get("/portal/transfers/{$incoming->id}")->assertOk()->assertSee($incoming->payout_code);
    }

    public function test_guests_are_sent_to_the_right_login(): void
    {
        $this->get('/staff')->assertRedirect('/staff/login');
        $this->get('/portal')->assertRedirect('/portal/login');
    }
}
