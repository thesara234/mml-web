<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\ExchangeRate;
use App\Models\FeeTier;
use App\Models\Office;
use App\Models\StaffUser;
use App\Models\StockItem;
use App\Models\Transaction;
use App\Services\Actor;
use App\Services\TransactionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Demo data. Office addresses, phone numbers, bank accounts, rates and fees are
 * SAMPLE values — replace them with the business's real details before going live.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Offices ───────────────────────────────────────────────
        $akl = Office::create([
            'code' => 'AKL-OTA', 'name' => 'MML Otahuhu (Head Office NZ)', 'office_type' => 'both',
            'country' => 'New Zealand', 'country_code' => 'NZ', 'city' => 'Auckland',
            'address' => 'Great South Road, Otahuhu, Auckland', 'phone' => '+64 9 000 0000',
            'email' => 'otahuhu@mmltonga.com', 'opening_hours' => 'Mon–Fri 8:30am–5pm, Sat 9am–1pm',
            'currency' => 'NZD', 'bank_name' => 'ANZ', 'bank_account_name' => 'Fietokoni & Meafakatonga (NZ) Ltd',
            'bank_account_number' => '01-0000-0000000-00',
        ]);
        $aus = Office::create([
            'code' => 'AUS-ROB', 'name' => 'Melie Mei Langi Robinvale (VIC)', 'office_type' => 'sender',
            'country' => 'Australia', 'country_code' => 'AU', 'city' => 'Robinvale, VIC',
            'address' => 'Robinvale, Victoria', 'phone' => '+61 3 0000 0000',
            'opening_hours' => 'Mon–Fri 9am–5pm', 'currency' => 'AUD',
            'bank_name' => 'Commonwealth Bank', 'bank_account_name' => 'Melie Mei Langi', 'bank_account_number' => '000-000 00000000',
        ]);
        $tbu = Office::create([
            'code' => 'TBU-KIN', 'name' => "MML Kinikinilau Supermarket, Ma'ufanga", 'office_type' => 'both',
            'country' => 'Tonga', 'country_code' => 'TO', 'city' => "Nuku'alofa",
            'address' => "Ma'ufanga, Tongatapu", 'phone' => '+676 00 000',
            'opening_hours' => 'Mon–Sat 8am–6pm', 'currency' => 'TOP', 'has_store' => true,
            'bank_name' => 'ANZ Tonga', 'bank_account_name' => 'Melie Mei Langi Ltd', 'bank_account_number' => '0000000',
        ]);
        $vav = Office::create([
            'code' => 'VAV-NEI', 'name' => "MML Vava'u, Neiafu", 'office_type' => 'receiver',
            'country' => 'Tonga', 'country_code' => 'TO', 'city' => 'Neiafu',
            'address' => "Neiafu, Vava'u", 'phone' => '+676 70 000', 'opening_hours' => 'Mon–Fri 8:30am–4:30pm, Sat 9am–12pm',
            'currency' => 'TOP', 'has_store' => true,
        ]);
        $hpa = Office::create([
            'code' => 'HPA-PAN', 'name' => "MML Ha'apai, Pangai", 'office_type' => 'receiver',
            'country' => 'Tonga', 'country_code' => 'TO', 'city' => 'Pangai',
            'address' => "Pangai, Lifuka, Ha'apai", 'phone' => '+676 60 000', 'opening_hours' => 'Mon–Fri 9am–4pm',
            'currency' => 'TOP', 'has_store' => false,
        ]);
        Office::create([
            'code' => 'EUA-OHO', 'name' => "MML 'Eua, 'Ohonua", 'office_type' => 'receiver',
            'country' => 'Tonga', 'country_code' => 'TO', 'city' => "'Ohonua",
            'address' => "'Ohonua, 'Eua", 'opening_hours' => 'Mon–Fri 9am–4pm', 'currency' => 'TOP',
        ]);

        // ── Staff ─────────────────────────────────────────────────
        $admin = StaffUser::create(['office_id' => $akl->id, 'given_name' => 'Siosaia', 'family_name' => 'Admin', 'email' => 'admin@mmltonga.com', 'password' => 'password', 'role' => 'admin']);
        $teller = StaffUser::create(['office_id' => $akl->id, 'given_name' => 'Lupe', 'family_name' => 'Vaipulu', 'email' => 'teller.nz@mmltonga.com', 'password' => 'password', 'role' => 'teller']);
        StaffUser::create(['office_id' => $akl->id, 'given_name' => 'Tevita', 'family_name' => 'Manu', 'email' => 'manager.nz@mmltonga.com', 'password' => 'password', 'role' => 'manager']);
        $store = StaffUser::create(['office_id' => $tbu->id, 'given_name' => 'Salote', 'family_name' => 'Kauvaka', 'email' => 'store.tonga@mmltonga.com', 'password' => 'password', 'role' => 'teller']);
        StaffUser::create(['office_id' => $vav->id, 'given_name' => 'Pita', 'family_name' => 'Lolohea', 'email' => 'vavau@mmltonga.com', 'password' => 'password', 'role' => 'teller']);

        // ── Rates & fees (sample) ─────────────────────────────────
        $today = now()->toDateString();
        foreach ([['NZD', 'TOP', 1.3600], ['TOP', 'NZD', 0.7042], ['AUD', 'TOP', 1.5700], ['USD', 'TOP', 2.3400]] as [$l, $f, $r]) {
            ExchangeRate::create(['local_currency' => $l, 'foreign_currency' => $f, 'rate' => $r - 0.01, 'effective_date' => now()->subDays(20)->toDateString(), 'set_by_staff_id' => $admin->id]);
            ExchangeRate::create(['local_currency' => $l, 'foreign_currency' => $f, 'rate' => $r, 'effective_date' => $today, 'set_by_staff_id' => $admin->id]);
        }
        foreach ([[0, 100, 'flat', 5], [100.01, 500, 'flat', 10], [500.01, 1000, 'flat', 15], [1000.01, null, 'percent', 1.5]] as [$min, $max, $type, $v]) {
            FeeTier::create(['local_currency' => 'NZD', 'foreign_currency' => 'TOP', 'min_amount' => $min, 'max_amount' => $max, 'fee_type' => $type, 'fee_value' => $v]);
        }
        foreach ([[0, 200, 'flat', 8], [200.01, null, 'percent', 2]] as [$min, $max, $type, $v]) {
            FeeTier::create(['local_currency' => 'AUD', 'foreign_currency' => 'TOP', 'min_amount' => $min, 'max_amount' => $max, 'fee_type' => $type, 'fee_value' => $v]);
        }
        FeeTier::create(['local_currency' => 'TOP', 'foreign_currency' => 'NZD', 'min_amount' => 0, 'max_amount' => null, 'fee_type' => 'percent', 'fee_value' => 3]);

        // ── Store stock (prices in TOP, sample) ───────────────────
        // [category, name, unit, price, picture in public/images/products]
        $catalogue = [
            ['Groceries', 'Rice 20kg bag', 'bag', 68.00, 'rice-20kg'], ['Groceries', 'Flour 10kg', 'bag', 32.50, 'flour-10kg'], ['Groceries', 'Sugar 5kg', 'bag', 19.90, 'sugar-5kg'],
            ['Groceries', 'Cooking oil 4L', 'bottle', 26.00, 'cooking-oil-4l'], ['Groceries', 'Instant noodles (carton 30)', 'carton', 24.00, 'instant-noodles-30'],
            ['Groceries', 'Tinned fish (carton 24)', 'carton', 58.00, 'tinned-fish-24'], ['Groceries', 'Corned beef 340g (pisupo)', 'tin', 9.80, 'corned-beef-340g'],
            ['Meat', 'Lamb flaps 10kg (sipi)', 'box', 95.00, 'lamb-flaps-10kg'], ['Meat', 'Chicken legs 10kg', 'box', 72.00, 'chicken-legs-10kg'], ['Meat', 'Mutton chops 5kg', 'box', 64.00, 'mutton-chops-5kg'],
            ['Household', 'Laundry powder 5kg', 'bag', 29.00, 'laundry-powder-5kg'], ['Household', 'Solar lantern', 'each', 45.00, 'solar-lantern'],
            ['Building', 'Cement 40kg', 'bag', 31.00, 'cement-40kg'], ['Building', 'Corrugated iron sheet 3m', 'sheet', 48.00, 'corrugated-iron-3m'],
            ['Special occasion', 'Pig (whole, medium)', 'each', 450.00, 'pig-whole'], ['Special occasion', 'Mat & tapa set (kie & ngatu)', 'set', 380.00, 'mat-tapa-set'],
        ];
        $i = 1;
        foreach ($catalogue as [$cat, $name, $unit, $price, $pic]) {
            StockItem::create(['office_id' => $tbu->id, 'sku' => 'TBU-'.str_pad($i++, 3, '0', STR_PAD_LEFT), 'name' => $name, 'category' => $cat, 'unit' => $unit, 'price' => $price, 'currency' => 'TOP', 'quantity_on_hand' => rand(4, 80), 'image_path' => "images/products/{$pic}.jpg"]);
        }
        foreach (array_slice($catalogue, 0, 9) as [$cat, $name, $unit, $price, $pic]) {
            StockItem::create(['office_id' => $vav->id, 'sku' => 'VAV-'.str_pad($i++, 3, '0', STR_PAD_LEFT), 'name' => $name, 'category' => $cat, 'unit' => $unit, 'price' => round($price * 1.05, 2), 'currency' => 'TOP', 'quantity_on_hand' => rand(3, 40), 'image_path' => "images/products/{$pic}.jpg"]);
        }

        // ── Customers ─────────────────────────────────────────────
        $mk = fn (array $a) => Customer::create($a + ['customer_code' => Customer::generateCustomerCode(), 'created_by_office_id' => $akl->id, 'registered_by_staff_id' => $teller->id, 'is_verified' => true, 'verified_at' => now()->subMonths(2), 'id_type' => 'passport']);

        $sione = $mk(['given_name' => 'Sione', 'family_name' => 'Taufa', 'date_of_birth' => '1985-05-12', 'gender' => 'male', 'residential_address' => '12 Church Street, Otahuhu', 'city' => 'Auckland', 'country' => 'New Zealand', 'phone' => '+64211234567', 'email' => 'sione.taufa@example.com', 'password' => 'password', 'id_number' => 'LA123456']);
        $ana = $mk(['given_name' => 'Ana', 'family_name' => 'Fifita', 'date_of_birth' => '1979-11-02', 'gender' => 'female', 'residential_address' => '4 Mason Avenue, Otahuhu', 'city' => 'Auckland', 'country' => 'New Zealand', 'phone' => '+64227654321', 'email' => 'ana.fifita@example.com', 'password' => 'password', 'id_number' => 'LB765432']);
        $viliami = $mk(['given_name' => 'Viliami', 'family_name' => 'Kata', 'date_of_birth' => '1990-03-18', 'gender' => 'male', 'residential_address' => '8 Bromley Road, Robinvale', 'city' => 'Robinvale', 'country' => 'Australia', 'phone' => '+61400111222', 'email' => 'viliami.kata@example.com', 'password' => 'password', 'created_by_office_id' => $aus->id]);
        $mele = $mk(['given_name' => 'Mele', 'family_name' => 'Taufa', 'date_of_birth' => '1958-09-03', 'gender' => 'female', 'residential_address' => "Ma'ufanga", 'city' => "Nuku'alofa", 'country' => 'Tonga', 'phone' => '+6767712345', 'email' => 'mele.taufa@example.com', 'password' => 'password', 'id_type' => 'national_id']);
        $losana = $mk(['given_name' => 'Losana', 'family_name' => 'Fifita', 'date_of_birth' => '1962-01-25', 'gender' => 'female', 'residential_address' => 'Neiafu', 'city' => 'Neiafu', 'country' => 'Tonga', 'phone' => '+6767723456', 'registered_by_customer_id' => $ana->id, 'registered_by_staff_id' => null, 'is_verified' => false, 'verified_at' => null]);
        $semisi = $mk(['given_name' => 'Semisi', 'family_name' => 'Taufa', 'date_of_birth' => '2003-07-14', 'gender' => 'male', 'residential_address' => "Kolomotu'a", 'city' => "Nuku'alofa", 'country' => 'Tonga', 'phone' => '+6768834567', 'registered_by_customer_id' => $sione->id, 'registered_by_staff_id' => null]);
        $ofa = $mk(['given_name' => "'Ofa", 'family_name' => 'Havili', 'date_of_birth' => '1970-12-09', 'gender' => 'female', 'residential_address' => 'Pangai', 'city' => 'Pangai', 'country' => 'Tonga', 'phone' => '+6768845678']);

        $sione->receivers()->attach([$mele->id => ['relationship' => 'Mother', 'preferred_office_id' => $tbu->id], $semisi->id => ['relationship' => 'Brother', 'preferred_office_id' => $tbu->id], $ofa->id => ['relationship' => 'Aunty', 'preferred_office_id' => $hpa->id]]);
        $ana->receivers()->attach([$losana->id => ['relationship' => 'Mother', 'preferred_office_id' => $vav->id], $mele->id => ['relationship' => 'Friend', 'preferred_office_id' => $tbu->id]]);
        $viliami->receivers()->attach([$mele->id => ['relationship' => 'Aunty', 'preferred_office_id' => $tbu->id]]);

        // ── Transfers in every state ──────────────────────────────
        $svc = app(TransactionService::class);
        $items = StockItem::where('office_id', $tbu->id)->pluck('id', 'name');
        $base = ['local_currency' => 'NZD', 'foreign_currency' => 'TOP', 'purpose' => 'Family support', 'sender_income_source' => 'Salary / wages'];

        // History over the last two weeks: paid and collected
        foreach (range(13, 1) as $daysAgo) {
            Carbon::setTestNow(now()->subDays($daysAgo)->setTime(rand(9, 16), rand(0, 59)));
            foreach (range(1, rand(1, 3)) as $_) {
                $sender = [$sione, $ana][rand(0, 1)];
                $receiver = $sender->is($sione) ? [$mele, $semisi][rand(0, 1)] : $losana;
                $t = $svc->createAtCounter($teller, $sender, $base + ['transfer_type' => 'money', 'amount' => [100, 150, 200, 250, 300, 500, 750][rand(0, 6)], 'receiver_customer_id' => $receiver->id, 'payment_method' => 'cash'], true);
                if ($daysAgo > 1) {
                    $svc->payout($t->load('items'), $t->receiver_office_id === $vav->id ? StaffUser::where('office_id', $vav->id)->first() : $store, $t->payout_code, true);
                }
            }
            Carbon::setTestNow();
        }

        // Ready to collect: money for Mele, and shopping for Mele
        $svc->createAtCounter($teller, $sione, $base + ['transfer_type' => 'money', 'amount' => 400, 'receiver_customer_id' => $mele->id, 'payment_method' => 'cash'], true);
        $svc->createAtCounter($teller, $sione, $base + ['transfer_type' => 'goods', 'amount' => 250, 'receiver_customer_id' => $mele->id, 'receiver_office_id' => $tbu->id, 'payment_method' => 'cash', 'purpose' => 'Funeral / faikava / special occasion',
            'items' => [['stock_item_id' => $items['Rice 20kg bag'], 'quantity' => 2], ['stock_item_id' => $items['Lamb flaps 10kg (sipi)'], 'quantity' => 1], ['stock_item_id' => $items['Tinned fish (carton 24)'], 'quantity' => 1]]], true);

        // Submitted in the app, waiting for internet-banking payment
        $svc->submit($ana, $base + ['transfer_type' => 'money', 'amount' => 300, 'receiver_customer_id' => $losana->id, 'payment_method' => 'internet_banking'], Actor::customer($ana));
        $svc->submit($viliami, ['local_currency' => 'AUD', 'foreign_currency' => 'TOP', 'transfer_type' => 'money', 'amount' => 500, 'receiver_customer_id' => $mele->id, 'payment_method' => 'internet_banking', 'purpose' => 'Education / school fees', 'sender_income_source' => 'Seasonal work (RSE)'], Actor::customer($viliami));

        // On hold
        $held = $svc->submit($sione, $base + ['transfer_type' => 'money', 'amount' => 2500, 'receiver_customer_id' => $ofa->id, 'payment_method' => 'internet_banking', 'purpose' => 'Building / housing'], Actor::customer($sione));
        $svc->hold($held, $admin, 'Amount over NZD 2,000 — waiting for proof of funds.');

        // Cancelled & refunded
        $c = $svc->submit($ana, $base + ['transfer_type' => 'money', 'amount' => 120, 'receiver_customer_id' => $losana->id, 'payment_method' => 'cash'], Actor::customer($ana));
        $svc->cancel($c, Actor::customer($ana), 'Cancelled by sender in the app.');
        $r = $svc->createAtCounter($teller, $sione, $base + ['transfer_type' => 'money', 'amount' => 180, 'receiver_customer_id' => $semisi->id, 'payment_method' => 'cash'], true);
        $svc->refund($r, $teller, 'Receiver travelling overseas — sender asked for the money back.');

        // Saved for later (draft)
        $svc->saveDraft($sione, $base + ['transfer_type' => 'goods', 'amount' => 150, 'receiver_customer_id' => $mele->id, 'receiver_office_id' => $tbu->id,
            'items' => [['stock_item_id' => $items['Flour 10kg'], 'quantity' => 1], ['stock_item_id' => $items['Sugar 5kg'], 'quantity' => 2]]], Actor::customer($sione));

        // ── Chat ──────────────────────────────────────────────────
        $conv = Conversation::forCustomer($sione);
        $conv->messages()->create(['sender_type' => 'customer', 'body' => "Mālō e lelei! Can my mum collect the shopping at Kinikinilau this Saturday?", 'created_at' => now()->subHours(3)]);
        $conv->messages()->create(['sender_type' => 'staff', 'staff_user_id' => $teller->id, 'body' => "Mālō e lelei Sione. Yes — Kinikinilau is open Saturday 8am–6pm. She just needs her photo ID and the 6-digit collection code from her app.", 'created_at' => now()->subHours(2), 'read_at' => now()->subHour()]);
        $conv->messages()->create(['sender_type' => 'customer', 'body' => 'Mālō ʻaupito 🙏', 'created_at' => now()->subMinutes(40)]);
        $conv->update(['last_message_at' => now()->subMinutes(40)]);

        $conv2 = Conversation::forCustomer($ana);
        $conv2->messages()->create(['sender_type' => 'customer', 'body' => "I've paid the NZD 310 by internet banking with my reference. How long until Losana can collect in Vava'u?", 'created_at' => now()->subMinutes(15)]);
        $conv2->update(['last_message_at' => now()->subMinutes(15)]);
    }
}
