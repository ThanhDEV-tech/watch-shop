<?php

namespace App\Services;

use App\Models\AiChatSession;
use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AiChatService
{
    public function __construct(private readonly OpenAiService $openAiService) {}

    /**
     * @return array{session: AiChatSession, message: string, product_suggestions: array<int, array<string, mixed>>}
     *
     * @throws AuthorizationException
     */
    public function chat(?User $user, string $message, ?int $sessionId, ?string $sessionToken): array
    {
        $session = $this->resolveSession($user, $sessionId, $sessionToken, $message);
        $intent = $this->detectIntent($message);
        $productSuggestions = $intent === 'shopping'
            ? $this->findProductSuggestions($message)
            : [];

        $session->messages()->create([
            'role' => 'user',
            'content' => $message,
        ]);

        $reply = match ($intent) {
            'outside' => $this->outsideScopeReply(),
            'policy' => $this->policyReply($message),
            default => $this->openAiService->chat(
                $this->systemPrompt($intent, $productSuggestions),
                $this->recentMessages($session),
            ),
        };

        if ($intent === 'shopping') {
            $reply = $this->ensureSuggestedProductsAreMentioned($reply, $productSuggestions);
        }

        $session->messages()->create([
            'role' => 'assistant',
            'content' => $reply,
        ]);

        return [
            'session' => $session,
            'message' => $reply,
            'product_suggestions' => $productSuggestions,
        ];
    }

    /** @throws AuthorizationException */
    public function ownedSession(User $user, int $sessionId): AiChatSession
    {
        $session = AiChatSession::query()->findOrFail($sessionId);

        if ($session->user_id !== $user->id) {
            throw new AuthorizationException('Bạn không có quyền truy cập phiên chat này.');
        }

        return $session;
    }

    /** @throws AuthorizationException */
    private function resolveSession(?User $user, ?int $sessionId, ?string $sessionToken, string $message): AiChatSession
    {
        if ($sessionId) {
            $session = AiChatSession::query()->findOrFail($sessionId);

            if ($session->user_id && $session->user_id !== $user?->id) {
                throw new AuthorizationException('Bạn không có quyền truy cập phiên chat này.');
            }

            if (! $session->user_id && (! $session->guest_token || $session->guest_token !== $sessionToken)) {
                throw new AuthorizationException('Phiên chat không hợp lệ. Vui lòng mở lại trợ lý AI.');
            }

            return $session;
        }

        return AiChatSession::query()->create([
            'user_id' => $user?->id,
            'guest_token' => $user ? null : ($sessionToken ?: (string) Str::uuid()),
            'title' => Str::limit($message, 80),
        ]);
    }

    /** @return array<int, array{role: string, content: string}> */
    private function recentMessages(AiChatSession $session): array
    {
        return $session->messages()
            ->latest('id')
            ->limit(10)
            ->get()
            ->reverse()
            ->map(fn ($message): array => [
                'role' => $message->role,
                'content' => $message->content,
            ])
            ->values()
            ->all();
    }

    private function detectIntent(string $message): string
    {
        $normalized = Str::lower(Str::ascii($message));

        $policyKeywords = [
            'bao hanh', 'doi tra', 'hoan tien', 'refund', 'van chuyen', 'shipping',
            'giao hang', 'phi ship', 'thanh toan', 'vnpay', 'size', 'kich co',
            'duong kinh', 'co tay', 'don hang', 'stock', 'ton kho',
        ];

        if (Str::contains($normalized, $policyKeywords)) {
            return 'policy';
        }

        $outsideKeywords = [
            'dien thoai', 'laptop', 'may tinh', 'xe may', 'o to', 'do an', 'quan ao',
            'giay dep', 'bong da', 'thoi tiet', 'lap trinh', 'khoa hoc',
        ];

        if (Str::contains($normalized, $outsideKeywords)) {
            return 'outside';
        }

        return 'shopping';
    }

    /** @return array<int, array<string, mixed>> */
    private function findProductSuggestions(string $message): array
    {
        $filters = $this->extractFilters($message);

        $products = $this->querySuggestionCandidates($filters);

        if ($products->count() < 3 && $filters['category_slug']) {
            $relaxedFilters = [...$filters, 'category_slug' => null];
            $products = $products
                ->merge($this->querySuggestionCandidates($relaxedFilters))
                ->unique(fn (array $item): int => $item['product']->id)
                ->values();
        }

        if ($products->count() < 3 && $filters['gender']) {
            $relaxedFilters = [...$filters, 'gender' => null];
            $products = $products
                ->merge($this->querySuggestionCandidates($relaxedFilters))
                ->unique(fn (array $item): int => $item['product']->id)
                ->values();
        }

        return $products
            ->sortBy([
                ['score', 'desc'],
                ['min_price', 'asc'],
            ])
            ->take(3)
            ->map(fn (array $item): array => $this->suggestionPayload($item['product'], (float) $item['min_price']))
            ->values()
            ->all();
    }

    /**
     * @param  array{budget_max: int|null, category_slug: string|null, gender: string|null, occasion: string|null}  $filters
     * @return Collection<int, array{product: Product, score: int, min_price: float}>
     */
    private function querySuggestionCandidates(array $filters): Collection
    {
        $products = Product::query()
            ->with([
                'brand',
                'category',
                'collections',
                'images',
                'variants' => fn ($query) => $query
                    ->where('is_active', true)
                    ->where('stock_quantity', '>', 0),
            ])
            ->where('status', 'active')
            ->whereHas('variants', function ($query) use ($filters): void {
                $query->where('is_active', true)
                    ->where('stock_quantity', '>', 0);

                if ($filters['budget_max']) {
                    $query->whereRaw('COALESCE(discount_price, price) <= ?', [$filters['budget_max']]);
                }
            })
            ->when($filters['gender'], function ($query) use ($filters): void {
                $targets = $filters['gender'] === 'unisex'
                    ? ['unisex']
                    : [$filters['gender'], 'unisex'];

                $query->whereIn('gender_target', $targets);
            })
            ->when($filters['category_slug'], fn ($query) => $query
                ->whereHas('category', fn ($categoryQuery) => $categoryQuery
                    ->where('slug', $filters['category_slug'])))
            ->get()
            ->map(fn (Product $product): array => [
                'product' => $product,
                'score' => $this->scoreProduct($product, $filters),
                'min_price' => $this->minVariantPrice($product->variants),
            ])
            ->sortBy([
                ['score', 'desc'],
                ['min_price', 'asc'],
            ])
            ->take(3)
            ->values();

        return $products;
    }

    /** @return array{budget_max: int|null, category_slug: string|null, gender: string|null, occasion: string|null} */
    private function extractFilters(string $message): array
    {
        $normalized = Str::lower(Str::ascii($message));

        return [
            'budget_max' => $this->extractBudget($normalized),
            'category_slug' => $this->extractCategorySlug($normalized),
            'gender' => $this->extractGender($normalized),
            'occasion' => $this->extractOccasion($normalized),
        ];
    }

    private function extractBudget(string $normalized): ?int
    {
        if (preg_match('/(\d+(?:[\.,]\d+)?)\s*(trieu|tr|m)\b/u', $normalized, $matches)) {
            return (int) round(((float) str_replace(',', '.', $matches[1])) * 1_000_000);
        }

        if (preg_match('/(\d+(?:[\.,]\d+)?)\s*(nghin|ngan|k)\b/u', $normalized, $matches)) {
            return (int) round(((float) str_replace(',', '.', $matches[1])) * 1_000);
        }

        if (preg_match('/(\d{6,8})/u', $normalized, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function extractCategorySlug(string $normalized): ?string
    {
        return match (true) {
            Str::contains($normalized, ['toi gian', 'minimal', 'mong', 'don gian']) => 'minimal-watch',
            Str::contains($normalized, ['the thao', 'sport', 'chay bo', 'nang dong']) => 'sport-watch',
            Str::contains($normalized, ['cong so', 'van phong', 'dress', 'thanh lich', 'lich su', 'tie c', 'tiec']) => 'dress-watch',
            Str::contains($normalized, ['casual', 'hang ngay', 'di choi', 'cuoi tuan']) => 'casual-watch',
            Str::contains($normalized, ['sport casual', 'di chuyen', 'travel']) => 'sport-casual',
            default => null,
        };
    }

    private function extractGender(string $normalized): ?string
    {
        return match (true) {
            Str::contains($normalized, ['nu', 'ban gai', 'vo', 'me']) => 'women',
            Str::contains($normalized, ['nam', 'ban trai', 'chong', 'bo']) => 'men',
            Str::contains($normalized, ['unisex', 'cap doi', 'doi']) => 'unisex',
            default => null,
        };
    }

    private function extractOccasion(string $normalized): ?string
    {
        return match (true) {
            Str::contains($normalized, ['qua', 'tang', 'sinh nhat']) => 'gifting',
            Str::contains($normalized, ['cong so', 'van phong', 'di lam']) => 'office',
            Str::contains($normalized, ['tiec', 'evening', 'hen ho']) => 'evening',
            Str::contains($normalized, ['du lich', 'di chuyen', 'cuoi tuan']) => 'travel',
            default => null,
        };
    }

    /** @param Collection<int, mixed> $variants */
    private function minVariantPrice(Collection $variants): float
    {
        return (float) $variants
            ->map(fn ($variant) => (float) ($variant->discount_price ?? $variant->price))
            ->filter(fn (float $price) => $price > 0)
            ->min();
    }

    /** @param array{budget_max: int|null, category_slug: string|null, gender: string|null, occasion: string|null} $filters */
    private function scoreProduct(Product $product, array $filters): int
    {
        $score = 0;

        if ($filters['category_slug'] && $product->category?->slug === $filters['category_slug']) {
            $score += 50;
        }

        if ($filters['gender'] && in_array($product->gender_target, [$filters['gender'], 'unisex'], true)) {
            $score += 30;
        }

        if ($filters['budget_max'] && $this->minVariantPrice($product->variants) <= $filters['budget_max']) {
            $score += 20;
        }

        $collectionSlugs = $product->collections->pluck('slug')->all();

        if ($filters['occasion'] === 'office' && in_array('office-style', $collectionSlugs, true)) {
            $score += 12;
        }

        if ($filters['occasion'] === 'gifting' && in_array('evening-gifting', $collectionSlugs, true)) {
            $score += 12;
        }

        if ($filters['occasion'] === 'travel' && in_array('summer-2026', $collectionSlugs, true)) {
            $score += 8;
        }

        return $score;
    }

    private function suggestionPayload(Product $product, float $minPrice): array
    {
        $primaryImage = $product->images
            ->sortBy('display_order')
            ->firstWhere('is_primary', true)
            ?? $product->images->sortBy('display_order')->first();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'brand' => $product->brand?->name,
            'category' => $product->category?->name,
            'gender_target' => $product->gender_target,
            'thumbnail' => $primaryImage?->image_path ?? $product->thumbnail,
            'min_price' => $minPrice,
            'url' => '/products/'.$product->slug,
            'variants' => $product->variants
                ->sortBy(fn ($variant) => (float) ($variant->discount_price ?? $variant->price))
                ->take(2)
                ->map(fn ($variant): array => [
                    'sku' => $variant->sku,
                    'strap_color' => $variant->strap_color,
                    'dial_color' => $variant->dial_color,
                    'diameter_mm' => $variant->diameter_mm,
                    'movement_type' => $variant->movement_type,
                    'price' => (float) ($variant->discount_price ?? $variant->price),
                    'stock_quantity' => $variant->stock_quantity,
                ])
                ->values()
                ->all(),
        ];
    }

    /** @param array<int, array<string, mixed>> $productSuggestions */
    private function systemPrompt(string $intent, array $productSuggestions): string
    {
        $productContext = empty($productSuggestions)
            ? 'Không có sản phẩm phù hợp trong dữ liệu lọc hiện tại.'
            : collect($productSuggestions)
                ->map(fn (array $product): string => sprintf(
                    "- %s | Brand: %s | Category: %s | Gender: %s | Giá từ: %s VND | Link: %s | Variant còn hàng: %s",
                    $product['name'],
                    $product['brand'] ?? 'Watchora',
                    $product['category'] ?? 'N/A',
                    $product['gender_target'],
                    number_format((float) $product['min_price'], 0, ',', '.'),
                    $product['url'],
                    collect($product['variants'])->map(fn (array $variant): string => sprintf(
                        "%s, dây %s, mặt %s, %smm, %s, %s VND, tồn %s",
                        $variant['sku'],
                        $variant['strap_color'],
                        $variant['dial_color'],
                        $variant['diameter_mm'],
                        $variant['movement_type'],
                        number_format((float) $variant['price'], 0, ',', '.'),
                        $variant['stock_quantity'],
                    ))->implode('; '),
                ))
                ->implode("\n");

        return <<<PROMPT
Bạn là AI Shopping Assistant của Watchora, shop bán đồng hồ thời trang nam/nữ.

RÀNG BUỘC BẮT BUỘC:
- Chỉ gợi ý sản phẩm có trong danh sách được cung cấp trong context, TUYỆT ĐỐI không tự bịa tên sản phẩm/giá/thương hiệu không có trong dữ liệu được cung cấp.
- Nếu không tìm thấy sản phẩm phù hợp trong dữ liệu, nói rõ không tìm thấy, không được bịa ra sản phẩm giả.
- Chỉ trả lời câu hỏi về Watchora (sản phẩm, chính sách), từ chối lịch sự nếu hỏi ngoài phạm vi.
- Trả lời bằng tiếng Việt tự nhiên, gọn, có dấu đầy đủ. Không dùng giọng máy dịch.
- Khi viết link sản phẩm, dùng chính xác đường dẫn tương đối được cung cấp, ví dụ /products/slug. Không thêm domain, không đổi slug.
- Nếu context có từ 2 sản phẩm trở lên và người dùng hỏi tư vấn chọn sản phẩm, hãy nhắc 2-3 sản phẩm đầu tiên, mỗi sản phẩm 1 lý do ngắn. Nếu sản phẩm nào chỉ là lựa chọn gần phù hợp, nói rõ là phương án thay thế.

Ý định hiện tại: {$intent}

CHÍNH SÁCH WATCHORA CỐ ĐỊNH:
- Bảo hành: mỗi sản phẩm có warranty_months và warranty_note riêng; dữ liệu demo mặc định thường là bảo hành máy 12 tháng, không bảo hành hao mòn tự nhiên của dây đeo. Không cam kết thêm điều kiện ngoài thông tin này.
- Vận chuyển: khách chọn một shipping zone đang active khi checkout. Các zone khởi tạo gồm Nội thành, Ngoại thành, Tỉnh/thành khác. Phí ship lấy từ shipping_zones và được snapshot vào đơn hàng.
- Đổi trả/hoàn tiền: refund xử lý thủ công; hệ thống không gọi VNPay refund API. Admin có thể mark refunded cho đơn paid_stock_issue, paid hoặc shipping; tiền hoàn thực tế xử lý ngoài hệ thống theo liên hệ hỗ trợ.
- Thanh toán: MVP dùng VNPay Sandbox. Return URL chỉ hiển thị kết quả; IPN là nguồn cập nhật trạng thái đơn hàng.
- Chọn size: đường kính variant trong khoảng 24-50mm. Cổ tay nhỏ thường hợp 24-34mm, trung bình 35-40mm, lớn hoặc thích nổi bật có thể chọn 41mm trở lên; hãy nhắc khách kiểm tra variant cụ thể.

SẢN PHẨM ĐƯỢC PHÉP GỢI Ý:
{$productContext}
PROMPT;
    }

    private function outsideScopeReply(): string
    {
        return 'Mình là trợ lý của Watchora nên chỉ hỗ trợ về đồng hồ, sản phẩm đang bán và các chính sách như bảo hành, vận chuyển, thanh toán hoặc hoàn tiền. Với câu hỏi ngoài phạm vi này, mình xin phép không tư vấn để tránh trả lời sai nhé.';
    }

    private function policyReply(string $message): string
    {
        $normalized = Str::lower(Str::ascii($message));

        if (Str::contains($normalized, ['bao hanh', 'warranty'])) {
            return 'Chính sách bảo hành của Watchora dựa trên thông tin của từng sản phẩm: `warranty_months` là thời hạn bảo hành, còn `warranty_note` là ghi chú chi tiết. Với dữ liệu demo hiện tại, phần lớn sản phẩm ghi bảo hành máy 12 tháng và không bảo hành hao mòn tự nhiên của dây đeo. Mình không thêm điều kiện bảo hành nào ngoài thông tin này; nếu bạn gửi tên sản phẩm cụ thể, mình có thể nhắc bạn kiểm tra đúng ghi chú bảo hành trên trang sản phẩm.';
        }

        if (Str::contains($normalized, ['van chuyen', 'shipping', 'giao hang', 'phi ship'])) {
            return 'Watchora tính phí vận chuyển theo shipping zone do khách chọn ở bước checkout. Các zone khởi tạo gồm Nội thành, Ngoại thành và Tỉnh/thành khác; hệ thống chỉ hiển thị zone đang active. Phí ship được lấy từ bảng `shipping_zones` và snapshot vào đơn hàng để lịch sử không đổi nếu admin chỉnh phí sau này. MVP chưa có API hãng vận chuyển và không tự nhận diện zone từ địa chỉ.';
        }

        if (Str::contains($normalized, ['doi tra', 'hoan tien', 'refund'])) {
            return 'Refund/hoàn tiền trong MVP được xử lý thủ công. Hệ thống không gọi VNPay refund API. Admin có thể đánh dấu `refunded` cho đơn đang ở trạng thái `paid_stock_issue`, `paid` hoặc `shipping`, kèm `refund_note`; việc chuyển tiền thực tế được xử lý ngoài hệ thống qua kênh hỗ trợ.';
        }

        if (Str::contains($normalized, ['thanh toan', 'vnpay'])) {
            return 'Watchora MVP dùng VNPay Sandbox cho thanh toán. Return URL chỉ dùng để hiển thị kết quả cho khách; IPN từ VNPay mới là nguồn cập nhật trạng thái đơn hàng và xử lý tồn kho. Hệ thống chưa hỗ trợ COD hoặc cổng thanh toán production trong MVP.';
        }

        if (Str::contains($normalized, ['size', 'kich co', 'duong kinh', 'co tay'])) {
            return 'Size đồng hồ trong Watchora được thể hiện bằng `diameter_mm` trên từng variant, giới hạn 24-50mm. Gợi ý nhanh: cổ tay nhỏ thường hợp 24-34mm, cổ tay trung bình 35-40mm, còn 41mm trở lên hợp người thích mặt nổi bật hoặc cổ tay lớn. Bạn nên xem đúng variant trên trang sản phẩm vì mỗi màu/loại máy có thể có đường kính khác nhau.';
        }

        return 'Mình có thể trả lời các chính sách Watchora trong phạm vi bảo hành, vận chuyển, thanh toán, hoàn tiền/refund, trạng thái đơn hàng và chọn size đồng hồ. Bạn muốn xem chính sách nào cụ thể hơn?';
    }

    /** @param array<int, array<string, mixed>> $productSuggestions */
    private function ensureSuggestedProductsAreMentioned(string $reply, array $productSuggestions): string
    {
        $reply = $this->sanitizeProductLinks($reply, $productSuggestions);

        if (count($productSuggestions) <= 1) {
            return $reply;
        }

        $normalizedReply = Str::lower(Str::ascii($reply));
        $missingProducts = collect($productSuggestions)
            ->filter(fn (array $product): bool => ! Str::contains($normalizedReply, Str::lower(Str::ascii($product['name']))))
            ->take(2)
            ->values();

        if ($missingProducts->isEmpty()) {
            return $reply;
        }

        $extra = $missingProducts
            ->map(fn (array $product): string => sprintf(
                '- %s: phương án %s, giá từ %s, xem tại %s.',
                $product['name'],
                ($product['category'] ?? 'đồng hồ') === 'Minimal Watch' ? 'đúng phong cách tối giản' : 'gần phù hợp để cân nhắc thêm',
                number_format((float) $product['min_price'], 0, ',', '.').' VND',
                $product['url'],
            ))
            ->implode("\n");

        return trim($reply)."\n\nMột vài lựa chọn thật khác trong dữ liệu Watchora:\n".$extra;
    }

    /** @param array<int, array<string, mixed>> $productSuggestions */
    private function sanitizeProductLinks(string $reply, array $productSuggestions): string
    {
        $firstUrl = $productSuggestions[0]['url'] ?? null;

        if ($firstUrl) {
            $reply = preg_replace('/\]\(\s*#\s*\)/', ']('.$firstUrl.')', $reply, 1) ?? $reply;
        }

        return preg_replace('/\]\(\s*(\/products\/[a-z0-9\-]+)\s*\)/', ']($1)', $reply) ?? $reply;
    }
}
