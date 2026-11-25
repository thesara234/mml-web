<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Gives the standard store items a picture (public/images/products) if they don't have one yet,
 * so an existing database shows pictures on the groceries page without re-seeding.
 * Photos uploaded by staff are never overwritten.
 */
return new class extends Migration
{
    private const PICTURES = [
        'Rice 20kg bag' => 'rice-20kg',
        'Flour 10kg' => 'flour-10kg',
        'Sugar 5kg' => 'sugar-5kg',
        'Cooking oil 4L' => 'cooking-oil-4l',
        'Instant noodles (carton 30)' => 'instant-noodles-30',
        'Tinned fish (carton 24)' => 'tinned-fish-24',
        'Corned beef 340g (pisupo)' => 'corned-beef-340g',
        'Lamb flaps 10kg (sipi)' => 'lamb-flaps-10kg',
        'Chicken legs 10kg' => 'chicken-legs-10kg',
        'Mutton chops 5kg' => 'mutton-chops-5kg',
        'Laundry powder 5kg' => 'laundry-powder-5kg',
        'Solar lantern' => 'solar-lantern',
        'Cement 40kg' => 'cement-40kg',
        'Corrugated iron sheet 3m' => 'corrugated-iron-3m',
        'Pig (whole, medium)' => 'pig-whole',
        'Mat & tapa set (kie & ngatu)' => 'mat-tapa-set',
    ];

    public function up(): void
    {
        foreach (self::PICTURES as $name => $file) {
            DB::table('stock_items')->where('name', $name)
                ->where(fn ($q) => $q->whereNull('image_path')->orWhere('image_path', ''))
                ->update(['image_path' => "images/products/{$file}.jpg"]);
        }
    }

    public function down(): void
    {
        DB::table('stock_items')->where('image_path', 'like', 'images/products/%')->update(['image_path' => null]);
    }
};
