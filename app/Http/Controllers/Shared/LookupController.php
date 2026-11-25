<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\StockItem;
use App\Services\QuoteService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Read-only helpers used by the public site, the staff console, the portal and the app. */
class LookupController extends Controller
{
    public function quote(Request $request, QuoteService $quotes)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'local_currency' => ['required', 'string', 'size:3'],
            'foreign_currency' => ['required', 'string', 'size:3'],
            'amount_mode' => ['nullable', Rule::in(['send', 'receive'])],
            'discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Only staff may apply a discount.
        $discount = $request->user('staff') ? (float) ($data['discount'] ?? 0) : 0;

        return response()->json(
            $quotes->quote((float) $data['amount'], $data['local_currency'], $data['foreign_currency'], $data['amount_mode'] ?? 'send', $discount)->toArray()
        );
    }

    public function storeItems(Request $request, Office $office)
    {
        abort_unless($office->is_active && $office->has_store, 404, 'This office does not have a store.');

        $items = StockItem::where('office_id', $office->id)
            ->where('is_active', true)
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%'))
            ->orderBy('category')->orderBy('name')
            ->get();

        return response()->json([
            'office' => \App\Models\Transaction::officeArray($office),
            'categories' => $items->pluck('category')->filter()->unique()->values(),
            'items' => $items->map(fn (StockItem $i) => [
                'id' => $i->id, 'sku' => $i->sku, 'name' => $i->name, 'category' => $i->category,
                'description' => $i->description, 'unit' => $i->unit, 'price' => $i->price, 'currency' => $i->currency,
                'in_stock' => $i->quantity_on_hand, 'image_url' => $i->image_url,
            ])->values(),
        ]);
    }
}
