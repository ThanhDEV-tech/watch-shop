<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonImmutable;

class AdminDashboardService
{
    /** @return array<string, mixed> */
    public function stats(): array
    {
        $today = CarbonImmutable::today();
        $startDate = $today->subDays(6);
        $paidOrders = Order::query()
            ->where('status', 'paid')
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
        $coursesByStatus = Course::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->whereIn('status', ['pending', 'approved', 'rejected'])
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $pendingCourses = (int) ($coursesByStatus['pending'] ?? 0);

        return [
            'total_users' => User::query()->count(),
            'total_students' => User::query()->whereHas('role', fn ($query) => $query->where('name', 'student'))->count(),
            'total_instructors' => User::query()->whereHas('role', fn ($query) => $query->where('name', 'instructor'))->count(),
            'total_courses' => [
                'all' => Course::query()->count(),
                'pending' => $pendingCourses,
                'approved' => (int) ($coursesByStatus['approved'] ?? 0),
                'rejected' => (int) ($coursesByStatus['rejected'] ?? 0),
            ],
            'total_orders' => Order::query()->count(),
            'total_revenue' => (float) Order::query()->where('status', 'paid')->sum('total_amount'),
            'revenue_last_7_days' => $revenueLastSevenDays,
            'pending_courses_count' => $pendingCourses,
        ];
    }
}
