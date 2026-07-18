<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use InvalidArgumentException;

class ProductVariantSeeder extends Seeder
{
    private const METAL_COLORS = ['Đen', 'Bạc', 'Vàng gold', 'Vàng rose'];

    private const SOFT_COLORS = ['Đen', 'Nâu', 'Xanh navy', 'Trắng'];

    private const DIAL_COLORS = ['Đen', 'Bạc', 'Trắng', 'Xanh navy'];

    private const BASE_PRICES = [
        'monarch-heritage-silver' => 3890000,
        'aurora-rose-petite' => 3290000,
        'velvet-evening-classic' => 4590000,
        'urban-black-tie' => 5190000,
        'noble-coast-runner' => 2890000,
        'lumen-active-navy' => 2490000,
        'monarch-field-chrono' => 5990000,
        'aurora-sport-blush' => 2190000,
        'urban-daily-brown' => 1890000,
        'lumen-weekend-sand' => 1690000,
        'velvet-soft-ivory' => 2390000,
        'noble-campus-steel' => 2090000,
        'aurora-line-minimal' => 2790000,
        'monarch-thin-index' => 3490000,
        'lumen-mono-white' => 1490000,
        'velvet-moon-dial' => 4190000,
        'noble-city-hybrid' => 3690000,
        'urban-crest-commuter' => 3190000,
        'aurora-travel-lite' => 1990000,
        'monarch-weekender-auto' => 7490000,
        'lumen-couple-silver' => 4290000,
        'velvet-gift-rose' => 5590000,
        'noble-outdoor-graphite' => 6390000,
        'urban-everyday-pair' => 2590000,
    ];

    public function run(): void
    {
        $products = Product::query()->orderBy('id')->get();

        foreach ($products as $index => $product) {
            $allowedStrapColors = $this->allowedStrapColors((string) $product->strap_material);
            $variantCount = 2 + ($index % 3);
            $allOutOfStock = in_array($product->slug, [
                'aurora-sport-blush',
                'lumen-mono-white',
                'noble-outdoor-graphite',
            ], true);

            for ($i = 0; $i < $variantCount; $i++) {
                $price = $this->priceFor($product, $i);
                $stock = $allOutOfStock ? 0 : [12, 6, 0, 18][$i] ?? 9;
                $strapColor = $allowedStrapColors[$i % count($allowedStrapColors)];
                $dialColor = self::DIAL_COLORS[($index + $i) % count(self::DIAL_COLORS)];

                $variant = ProductVariant::query()->firstOrNew([
                    'product_id' => $product->id,
                    'strap_color' => $strapColor,
                    'dial_color' => $dialColor,
                    'diameter_mm' => $this->diameterFor((string) $product->gender_target, $i),
                    'movement_type' => $this->movementFor($product, $i),
                ]);

                $variant->fill([
                    'price' => $price,
                    'discount_price' => $i === 1 ? max(500000, $price - 250000) : null,
                    'stock_quantity' => $stock,
                    'image' => $product->thumbnail,
                    'is_active' => true,
                ])->save();
            }
        }
    }

    /** @return array<int, string> */
    private function allowedStrapColors(string $strapMaterial): array
    {
        $normalized = str($strapMaterial)->lower()->toString();

        if (str_contains($normalized, 'metal')) {
            return self::METAL_COLORS;
        }

        if (str_contains($normalized, 'leather')
            || str_contains($normalized, 'fabric')
            || str_contains($normalized, 'rubber')) {
            return self::SOFT_COLORS;
        }

        throw new InvalidArgumentException("Unsupported strap material: {$strapMaterial}");
    }

    private function priceFor(Product $product, int $variantIndex): int
    {
        $base = self::BASE_PRICES[(string) $product->slug] ?? 2500000;
        $movementPremium = $variantIndex === 2 ? 1200000 : 0;

        return min(15000000, max(500000, $base + ($variantIndex * 350000) + $movementPremium));
    }

    private function diameterFor(string $genderTarget, int $variantIndex): int
    {
        return match ($genderTarget) {
            'women' => [28, 30, 32, 34][$variantIndex] ?? 32,
            'unisex' => [34, 36, 38, 40][$variantIndex] ?? 38,
            default => [40, 42, 44, 46][$variantIndex] ?? 42,
        };
    }

    private function movementFor(Product $product, int $variantIndex): string
    {
        if (str_contains((string) $product->slug, 'auto') || $variantIndex === 2) {
            return 'automatic';
        }

        return 'quartz';
    }
}
