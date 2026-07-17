<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Carbon\CarbonImmutable;

class AdminDashboardService
{
    private const REVENUE_STATUSES = ['paid', 'shipping', 'completed'];

    private const LOW_STOCK_THRESHOLD = 5;

    /** @return array<string, mixed> */
    public function stats(): array
    {
        $today = CarbonImmutable::today();
        $startDate = $today->subDays(6);
        $paidOrders = Order::query()
            ->whereIn('status', self::REVENUE_STATUSES)
            ->whereBetween('paid_at', [$startDate->startOfDay(), $today->endOfDay()])
            ->get(['total_amount', 'paid_at']);
        $revenueByDate = $paidOrders
            ->groupBy(fn (Order $order): string => $order->paid_at->toDateString())
            ->map(fn ($orders): float => (float) $orders->sum('total_amount'));
        $revenueLastSevenDays = collect(range(0, 6))
            ->map(function (int $offset) use ($startDate, $revenueByDate): array {
                $date = $startDate->addDays($offset)->toDateString();

                return [
                    'date' => $date,
                    'total' => (float) ($revenueByDate[$date] ?? 0),
                ];
            })
            ->all();
        $productsByStatus = Product::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->whereIn('status', Product::STATUSES)
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $ordersByStatus = Order::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->whereIn('status', Order::STATUSES)
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $productStatusCounts = collect(Product::STATUSES)
            ->mapWithKeys(fn (string $status): array => [$status => (int) ($productsByStatus[$status] ?? 0)])
            ->all();
        $orderStatusCounts = collect(Order::STATUSES)
            ->mapWithKeys(fn (string $status): array => [$status => (int) ($ordersByStatus[$status] ?? 0)])
            ->all();
        $topSellingProducts = OrderItem::query()
            ->selectRaw('product_id, product_name, brand_name, SUM(quantity) as total_quantity, SUM(line_total) as total_revenue')
            ->whereHas('order', fn ($query) => $query->whereIn('status', self::REVENUE_STATUSES))
            ->groupBy('product_id', 'product_name', 'brand_name')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get()
            ->map(fn (OrderItem $item): array => [
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'brand_name' => $item->brand_name,
                'total_quantity' => (int) $item->total_quantity,
                'total_revenue' => (float) $item->total_revenue,
            ])
            ->all();

        return [
            'total_users' => User::query()->count(),
            'total_customers' => User::query()->whereHas('role', fn ($query) => $query->where('name', 'customer'))->count(),
            'total_products' => Product::query()->count(),
            'product_status_counts' => $productStatusCounts,
            'total_orders' => Order::query()->count(),
            'order_status_counts' => $orderStatusCounts,
            'needs_attention_count' => $orderStatusCounts['paid_stock_issue'],
            'total_revenue' => (float) Order::query()->whereIn('status', self::REVENUE_STATUSES)->sum('total_amount'),
            'revenue_last_7_days' => $revenueLastSevenDays,
            'revenue_statuses' => self::REVENUE_STATUSES,
            'top_selling_products' => $topSellingProducts,
            'low_stock_threshold' => self::LOW_STOCK_THRESHOLD,
            'low_stock_variants_count' => ProductVariant::query()
                ->where('stock_quantity', '<=', self::LOW_STOCK_THRESHOLD)
                ->count(),
        ];
    }
}
