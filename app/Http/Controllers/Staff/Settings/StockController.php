<?php

namespace App\Http\Controllers\Staff\Settings;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\StockItem;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $staff = $request->user('staff');
        $stores = Office::active()->where('has_store', true)->orderBy('name')->get();
        $storeId = (int) $request->query('store', $staff->office->has_store ? $staff->office_id : ($stores->first()->id ?? 0));
        $store = $stores->firstWhere('id', $storeId);

        $items = StockItem::where('office_id', $storeId)
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->query('q').'%'))
            ->orderBy('category')->orderBy('name')->get();

        return view('staff.settings.stock', compact('stores', 'store', 'items'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, true);
        $data['image_path'] = $request->file('image')?->store('stock', 'public');
        unset($data['image']);
        $data['currency'] = Office::findOrFail($data['office_id'])->currency;
        StockItem::create($data + ['is_active' => true]);

        return back()->with('success', 'Item added to the store.');
    }

    public function update(Request $request, StockItem $stockItem)
    {
        $data = $this->validated($request, false);
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('stock', 'public');
        }
        unset($data['image']);
        $data['is_active'] = $request->boolean('is_active');
        $stockItem->update($data);

        return back()->with('success', "{$stockItem->name} updated.");
    }

    private function validated(Request $request, bool $creating): array
    {
        return $request->validate([
            'office_id' => [$creating ? 'required' : 'prohibited', 'integer', 'exists:offices,id'],
            'sku' => ['nullable', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:500'],
            'unit' => ['required', 'string', 'max:20'],
            'price' => ['required', 'numeric', 'min:0'],
            'quantity_on_hand' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);
    }
}
