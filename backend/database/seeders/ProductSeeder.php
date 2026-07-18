<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $brands = Brand::query()->pluck('id', 'slug');
        $categories = Category::query()->pluck('id', 'slug');
        $collections = Collection::query()->pluck('id', 'slug');

        foreach ($this->products() as $index => $item) {
            $collectionSlugs = Arr::pull($item, 'collections', []);
            $brandSlug = Arr::pull($item, 'brand');
            $categorySlug = Arr::pull($item, 'category');
            Arr::pull($item, 'gallery_offset');
            Arr::pull($item, 'base_price');
            $gallery = $this->galleryFor($categorySlug, (string) $item['strap_material'], $index);

            $product = Product::query()->updateOrCreate(
                ['slug' => $item['slug']],
                [
                    ...$item,
                    'brand_id' => $brands[$brandSlug],
                    'category_id' => $categories[$categorySlug],
                    'thumbnail' => $gallery[0],
                    'status' => 'active',
                    'rating_avg' => $item['rating_avg'] ?? 0,
                ],
            );

            $product->images()->delete();

            foreach ($gallery as $imageIndex => $imageUrl) {
                $product->images()->create([
                    'image_path' => $imageUrl,
                    'alt_text' => $product->name,
                    'display_order' => $imageIndex + 1,
                    'is_primary' => $imageIndex === 0,
                ]);
            }

            $sync = [];
            foreach ($collectionSlugs as $displayOrder => $slug) {
                if (isset($collections[$slug])) {
                    $sync[$collections[$slug]] = ['display_order' => $displayOrder + 1];
                }
            }

            $product->collections()->sync($sync);
        }
    }

    /** @return array<int, string> */
    private function galleryFor(string $categorySlug, string $strapMaterial, int $offset): array
    {
        $dressLeather = [
            'https://images.unsplash.com/photo-1523170335258-f5ed11844a49?auto=format&fit=crop&w=900&q=85',
            'https://images.unsplash.com/photo-1524805444758-089113d48a6d?auto=format&fit=crop&w=900&q=85',
            'https://images.unsplash.com/photo-1434056886845-dac89ffe9b56?auto=format&fit=crop&w=900&q=85',
        ];
        $dressMetal = [
            'https://images.unsplash.com/photo-1547996160-81dfa63595aa?auto=format&fit=crop&w=900&q=85',
            'https://images.unsplash.com/photo-1523170335258-f5ed11844a49?auto=format&fit=crop&w=900&q=85',
            'https://images.unsplash.com/photo-1524805444758-089113d48a6d?auto=format&fit=crop&w=900&q=85',
        ];
        $minimalLeather = [
            'https://images.unsplash.com/photo-1524592094714-0f0654e20314?auto=format&fit=crop&w=900&q=85',
            'https://images.unsplash.com/photo-1523170335258-f5ed11844a49?auto=format&fit=crop&w=900&q=85',
            'https://images.unsplash.com/photo-1434056886845-dac89ffe9b56?auto=format&fit=crop&w=900&q=85',
        ];
        $minimalMetal = [
            'https://images.unsplash.com/photo-1524592094714-0f0654e20314?auto=format&fit=crop&w=900&q=85',
            'https://images.unsplash.com/photo-1547996160-81dfa63595aa?auto=format&fit=crop&w=900&q=85',
            'https://images.unsplash.com/photo-1524805444758-089113d48a6d?auto=format&fit=crop&w=900&q=85',
        ];
        $isMetal = str_contains(strtolower($strapMaterial), 'metal');
        $images = [
            'dress-watch' => [
                ...($isMetal ? $dressMetal : $dressLeather),
            ],
            'sport-watch' => [
                'https://images.unsplash.com/photo-1539874754764-5a96559165b0?auto=format&fit=crop&w=900&q=85',
                'https://images.unsplash.com/photo-1508685096489-7aacd43bd3b1?auto=format&fit=crop&w=900&q=85',
                'https://images.unsplash.com/photo-1560379353-319e3563cf54?auto=format&fit=crop&w=900&q=85',
            ],
            'casual-watch' => [
                'https://images.unsplash.com/photo-1524805444758-089113d48a6d?auto=format&fit=crop&w=900&q=85',
                'https://images.unsplash.com/photo-1434056886845-dac89ffe9b56?auto=format&fit=crop&w=900&q=85',
                'https://images.unsplash.com/photo-1523170335258-f5ed11844a49?auto=format&fit=crop&w=900&q=85',
            ],
            'minimal-watch' => [
                ...($isMetal ? $minimalMetal : $minimalLeather),
            ],
            'sport-casual' => [
                'https://images.unsplash.com/photo-1508685096489-7aacd43bd3b1?auto=format&fit=crop&w=900&q=85',
                'https://images.unsplash.com/photo-1560379353-319e3563cf54?auto=format&fit=crop&w=900&q=85',
                'https://images.unsplash.com/photo-1539874754764-5a96559165b0?auto=format&fit=crop&w=900&q=85',
            ],
        ];

        $pool = $images[$categorySlug];
        $shift = $offset % count($pool);

        return array_values(array_merge(
            array_slice($pool, $shift),
            array_slice($pool, 0, $shift),
        ));
    }

    /** @return array<int, array<string, mixed>> */
    private function products(): array
    {
        return [
            $this->product('monarch-heritage-silver', 'Monarch Heritage Silver', 'monarch-studio', 'dress-watch', 'men', 'Stainless Steel', 'Metal', ['office-style', 'evening-gifting'], 1, 3890000),
            $this->product('aurora-rose-petite', 'Aurora Rose Petite', 'aurora-time', 'dress-watch', 'women', 'Rose Gold Steel', 'Metal', ['evening-gifting', 'couple-watches'], 2, 3290000),
            $this->product('velvet-evening-classic', 'Velvet Evening Classic', 'velvet-hour', 'dress-watch', 'women', 'Polished Steel', 'Leather', ['evening-gifting'], 3, 4590000),
            $this->product('urban-black-tie', 'Urban Black Tie', 'urban-crest', 'dress-watch', 'men', 'Stainless Steel', 'Leather', ['office-style'], 4, 5190000),
            $this->product('noble-coast-runner', 'Noble Coast Runner', 'noble-coast', 'sport-watch', 'men', 'Brushed Steel', 'Rubber', ['summer-2026'], 5, 2890000),
            $this->product('lumen-active-navy', 'Lumen Active Navy', 'lumen-co', 'sport-watch', 'unisex', 'Steel Alloy', 'Fabric', ['summer-2026'], 6, 2490000),
            $this->product('monarch-field-chrono', 'Monarch Field Chrono', 'monarch-studio', 'sport-watch', 'men', 'Matte Steel', 'Rubber', [], 7, 5990000),
            $this->product('aurora-sport-blush', 'Aurora Sport Blush', 'aurora-time', 'sport-watch', 'women', 'Aluminum Alloy', 'Fabric', ['summer-2026'], 8, 2190000),
            $this->product('urban-daily-brown', 'Urban Daily Brown', 'urban-crest', 'casual-watch', 'men', 'Stainless Steel', 'Leather', ['office-style'], 9, 1890000),
            $this->product('lumen-weekend-sand', 'Lumen Weekend Sand', 'lumen-co', 'casual-watch', 'unisex', 'Steel Alloy', 'Fabric', ['summer-2026'], 10, 1690000),
            $this->product('velvet-soft-ivory', 'Velvet Soft Ivory', 'velvet-hour', 'casual-watch', 'women', 'Polished Steel', 'Leather', ['evening-gifting'], 11, 2390000),
            $this->product('noble-campus-steel', 'Noble Campus Steel', 'noble-coast', 'casual-watch', 'unisex', 'Stainless Steel', 'Metal', [], 12, 2090000),
            $this->product('aurora-line-minimal', 'Aurora Line Minimal', 'aurora-time', 'minimal-watch', 'women', 'Rose Gold Steel', 'Metal', ['office-style', 'couple-watches'], 13, 2790000),
            $this->product('monarch-thin-index', 'Monarch Thin Index', 'monarch-studio', 'minimal-watch', 'men', 'Stainless Steel', 'Leather', ['office-style'], 14, 3490000),
            $this->product('lumen-mono-white', 'Lumen Mono White', 'lumen-co', 'minimal-watch', 'unisex', 'Steel Alloy', 'Fabric', [], 15, 1490000),
            $this->product('velvet-moon-dial', 'Velvet Moon Dial', 'velvet-hour', 'minimal-watch', 'women', 'Polished Steel', 'Metal', ['evening-gifting'], 16, 4190000),
            $this->product('noble-city-hybrid', 'Noble City Hybrid', 'noble-coast', 'sport-casual', 'men', 'Brushed Steel', 'Rubber', ['summer-2026'], 17, 3690000),
            $this->product('urban-crest-commuter', 'Urban Crest Commuter', 'urban-crest', 'sport-casual', 'unisex', 'Stainless Steel', 'Metal', ['office-style'], 18, 3190000),
            $this->product('aurora-travel-lite', 'Aurora Travel Lite', 'aurora-time', 'sport-casual', 'women', 'Aluminum Alloy', 'Fabric', ['summer-2026'], 19, 1990000),
            $this->product('monarch-weekender-auto', 'Monarch Weekender Auto', 'monarch-studio', 'sport-casual', 'men', 'Stainless Steel', 'Leather', [], 20, 7490000),
            $this->product('lumen-couple-silver', 'Lumen Couple Silver', 'lumen-co', 'dress-watch', 'unisex', 'Stainless Steel', 'Metal', ['couple-watches', 'evening-gifting'], 21, 4290000),
            $this->product('velvet-gift-rose', 'Velvet Gift Rose', 'velvet-hour', 'minimal-watch', 'women', 'Rose Gold Steel', 'Metal', ['couple-watches', 'evening-gifting'], 22, 5590000),
            $this->product('noble-outdoor-graphite', 'Noble Outdoor Graphite', 'noble-coast', 'sport-watch', 'men', 'Matte Steel', 'Rubber', ['summer-2026'], 23, 6390000),
            $this->product('urban-everyday-pair', 'Urban Everyday Pair', 'urban-crest', 'casual-watch', 'unisex', 'Stainless Steel', 'Leather', ['couple-watches'], 24, 2590000),
        ];
    }

    /** @return array<string, mixed> */
    private function product(
        string $slug,
        string $name,
        string $brand,
        string $category,
        string $gender,
        string $caseMaterial,
        string $strapMaterial,
        array $collections,
        int $galleryOffset,
        int $basePrice,
    ): array {
        return [
            'name' => $name,
            'slug' => $slug,
            'brand' => $brand,
            'category' => $category,
            'gender_target' => $gender,
            'description' => $this->descriptionFor($name, $category, $strapMaterial),
            'content' => $this->contentFor($name, $category, $strapMaterial),
            'case_material' => $caseMaterial,
            'strap_material' => $strapMaterial,
            'glass_material' => 'Kính khoáng',
            'water_resistance' => in_array($category, ['sport-watch', 'sport-casual'], true) ? '5 ATM' : '3 ATM',
            'warranty_months' => 12,
            'warranty_note' => 'Bảo hành máy 12 tháng, không bảo hành hao mòn tự nhiên của dây đeo.',
            'collections' => $collections,
            'gallery_offset' => $galleryOffset,
            'rating_avg' => round(4 + (($galleryOffset % 10) / 10), 2),
            'base_price' => $basePrice,
        ];
    }

    private function descriptionFor(string $name, string $category, string $strapMaterial): string
    {
        $strap = $this->strapLabel($strapMaterial);

        return match ($category) {
            'dress-watch' => "{$name} là mẫu dress watch thanh lịch với {$strap}, hợp với áo sơ mi, vest nhẹ và những buổi gặp gỡ cần vẻ chỉn chu.",
            'sport-watch' => "{$name} có tinh thần năng động, mặt số rõ ràng và {$strap} chắc tay, phù hợp cho lịch trình di chuyển nhiều hoặc cuối tuần ngoài trời.",
            'casual-watch' => "{$name} là mẫu đồng hồ dễ đeo hằng ngày, đủ gọn để đi làm nhưng vẫn có điểm nhấn riêng khi phối cùng trang phục casual.",
            'minimal-watch' => "{$name} theo phong cách tối giản: mặt số thoáng, ít chi tiết thừa và dáng đeo nhẹ, hợp với người thích vẻ tinh tế kín đáo.",
            'sport-casual' => "{$name} cân bằng giữa dáng thể thao và sự lịch sự đời thường, dễ chuyển từ giờ làm sang cà phê cuối ngày.",
            default => "{$name} là mẫu đồng hồ thời trang dễ đeo, có nhiều lựa chọn màu dây, màu mặt và kích thước cho từng nhu cầu sử dụng.",
        };
    }

    private function contentFor(string $name, string $category, string $strapMaterial): string
    {
        $strap = $this->strapLabel($strapMaterial);

        return match ($category) {
            'dress-watch' => "{$name} dành cho khách cần một chiếc đồng hồ lịch sự nhưng không phô trương. Phần {$strap} giữ tổng thể gọn gàng, còn mặt số thanh thoát tạo cảm giác sang vừa đủ cho công sở, tiệc tối hoặc những dịp cần vẻ ngoài chỉn chu.",
            'sport-watch' => "{$name} phù hợp với khách thích đồng hồ khỏe khoắn, dễ nhìn và bền bỉ hơn trong sinh hoạt hằng ngày. Phần {$strap} tạo cảm giác chắc tay khi đeo, trong khi các lựa chọn màu mặt và đường kính giúp sản phẩm dễ hợp với nhiều cổ tay.",
            'casual-watch' => "{$name} là lựa chọn an toàn cho người muốn một chiếc đồng hồ đeo được nhiều dịp: đi làm, đi chơi hoặc làm quà tặng nhẹ nhàng. Thiết kế giữ sự cân bằng giữa tính ứng dụng và cảm giác có gu, không quá trang trọng nhưng vẫn đủ lịch sự.",
            'minimal-watch' => "{$name} ưu tiên sự sạch sẽ trong bố cục mặt số, đường nét mảnh và màu sắc dễ phối. Điểm hay của mẫu này nằm ở sự tiết chế: càng ít chi tiết thừa, chiếc đồng hồ càng dễ đi cùng áo sơ mi, áo thun trơn hoặc blazer nhẹ.",
            'sport-casual' => "{$name} dành cho khách muốn một mẫu đồng hồ linh hoạt hơn dress watch nhưng không quá hầm hố như sport watch thuần. Sản phẩm giữ dáng đeo chắc tay, phối được với áo polo, sơ mi casual hoặc áo khoác nhẹ khi ra ngoài cuối ngày.",
            default => "{$name} có nhiều biến thể màu dây, màu mặt, đường kính và loại máy để khách chọn đúng phiên bản hợp với cổ tay và phong cách cá nhân.",
        };
    }

    private function strapLabel(string $strapMaterial): string
    {
        return match ($strapMaterial) {
            'Metal' => 'dây kim loại',
            'Leather' => 'dây da',
            'Fabric' => 'dây vải',
            'Rubber' => 'dây cao su',
            default => 'dây đeo',
        };
    }
}
