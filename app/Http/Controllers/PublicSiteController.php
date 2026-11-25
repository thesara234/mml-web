<?php

namespace App\Http\Controllers;

use App\Models\ExchangeRate;
use App\Models\FeeTier;
use App\Models\Office;
use App\Models\StockItem;
use App\Models\Transaction;
use Illuminate\Http\Request;

/** The customer-facing website (a rebuilt mmltonga.com). */
class PublicSiteController extends Controller
{
    public function home()
    {
        return view('site.home', [
            'rates' => ExchangeRate::board(),
            'offices' => Office::active()->orderBy('country')->get(),
            'featured' => StockItem::with('office')->where('is_active', true)->where('quantity_on_hand', '>', 0)->inRandomOrder()->limit(4)->get(),
        ]);
    }

    public function rates()
    {
        return view('site.rates', [
            'rates' => ExchangeRate::board(),
            'fees' => FeeTier::where('is_active', true)->orderBy('local_currency')->orderBy('foreign_currency')->orderBy('min_amount')->get()
                ->groupBy(fn ($t) => $t->local_currency.' → '.$t->foreign_currency),
        ]);
    }

    public function shop(Request $request)
    {
        $stores = Office::active()->where('has_store', true)->orderBy('name')->get();
        $store = $stores->firstWhere('id', $request->integer('store')) ?? $stores->first();
        $items = $store ? StockItem::where('office_id', $store->id)->where('is_active', true)
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->query('category')))
            ->orderBy('category')->orderBy('name')->get() : collect();
        $categories = $store ? StockItem::where('office_id', $store->id)->where('is_active', true)->distinct()->pluck('category')->filter()->sort()->values() : collect();

        return view('site.shop', compact('stores', 'store', 'items', 'categories'));
    }

    public function locations()
    {
        return view('site.locations', ['offices' => Office::active()->orderBy('country')->orderBy('name')->get()->groupBy('country')]);
    }

    /** Public tracking: reference number + sender's family name → status only. */
    public function track(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $data = $request->validate(['reference_no' => ['required', 'string', 'max:30'], 'family_name' => ['required', 'string', 'max:80']]);
            $result = Transaction::with(['events', 'receiverOffice'])
                ->where('reference_no', strtoupper(trim($data['reference_no'])))
                ->where('status', '!=', Transaction::DRAFT)
                ->whereHas('senderCustomer', fn ($q) => $q->where('family_name', trim($data['family_name'])))
                ->first();
            if (! $result) {
                return back()->withErrors(['reference_no' => "We couldn't find a transfer with those details."])->withInput();
            }
        }

        return view('site.track', ['t' => $result]);
    }
}
