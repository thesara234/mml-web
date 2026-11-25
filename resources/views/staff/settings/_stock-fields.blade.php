<div class="form-grid">
    <div class="field span-2"><label>Name</label><input class="input" name="name" value="{{ $item->name }}" required></div>
    <div class="field"><label>Category</label><input class="input" name="category" value="{{ $item->category }}" list="stock-cats"></div>
    <div class="field"><label>SKU</label><input class="input" name="sku" value="{{ $item->sku }}"></div>
    <div class="field"><label>Price</label><input class="input" type="number" step="0.01" min="0" name="price" value="{{ $item->price }}" required></div>
    <div class="field"><label>Unit</label><input class="input" name="unit" value="{{ $item->unit }}" required placeholder="bag, box, each"></div>
    <div class="field"><label>Quantity in stock</label><input class="input" type="number" min="0" name="quantity_on_hand" value="{{ $item->quantity_on_hand ?? 0 }}" required></div>
    <div class="field"><label>Photo</label><input class="input" type="file" name="image" accept="image/*"></div>
    <div class="field span-2 mb-0"><label>Description</label><textarea class="textarea" name="description" style="min-height:60px">{{ $item->description }}</textarea></div>
</div>
<datalist id="stock-cats"><option>Groceries</option><option>Meat</option><option>Household</option><option>Building</option><option>Special occasion</option></datalist>
