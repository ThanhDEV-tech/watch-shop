<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewService
{
    /** @param array{rating: int, comment?: string|null} $data */
    public function upsert(User $user, Product $product, array $data): Review
    {
        if (! $this->canReviewProduct($user, $product)) {
            throw ValidationException::withMessages([
                'product' => ['Bạn chỉ có thể đánh giá sản phẩm đã nằm trong đơn hàng hoàn tất.'],
            ]);
        }

        return DB::transaction(function () use ($user, $product, $data): Review {
            $review = Review::query()->updateOrCreate(
                ['user_id' => $user->id, 'product_id' => $product->id],
                $data,
            );

            $this->refreshProductRating($product);

            return $review->load('user');
        });
    }

    public function delete(Review $review): void
    {
        DB::transaction(function () use ($review): void {
            $product = $review->product;
            $review->delete();

            if ($product) {
                $this->refreshProductRating($product);
            }
        });
    }

    public function canReviewProduct(User $user, Product $product): bool
    {
        return OrderItem::query()
            ->where('product_id', $product->id)
            ->whereHas('order', fn ($query) => $query
                ->where('user_id', $user->id)
                ->where('status', 'completed'))
            ->exists();
    }

    public function refreshProductRating(Product $product): void
    {
        $ratingAverage = (float) ($product->reviews()->avg('rating') ?? 0);
        $product->update(['rating_avg' => round($ratingAverage, 2)]);
    }
}
