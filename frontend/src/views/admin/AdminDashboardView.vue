<script setup>
import { computed, onMounted, ref } from 'vue'
import DashboardStatCard from '../../components/dashboard/DashboardStatCard.vue'
import RevenueBarChart from '../../components/dashboard/RevenueBarChart.vue'
import { getAdminDashboardStats } from '../../services/api'
import { formatCurrency } from '../../utils/formatCurrency'

const stats = ref(null)
const loading = ref(true)
const error = ref('')

const productCounts = computed(() => stats.value?.product_status_counts ?? {})
const orderCounts = computed(() => stats.value?.order_status_counts ?? {})

const fetchStats = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await getAdminDashboardStats()
    stats.value = response.data.data
  } catch (requestError) {
    error.value = requestError.response?.data?.message ?? 'Không thể tải dữ liệu quản trị.'
  } finally {
    loading.value = false
  }
}

onMounted(fetchStats)
</script>

<template>
  <main class="mx-auto w-full min-w-0 max-w-[1440px] p-margin-mobile md:p-gutter lg:p-lg">
    <div class="flex w-full min-w-0 flex-wrap items-end justify-between gap-md">
      <div class="w-full min-w-0 sm:w-auto">
        <p class="w-full font-mono text-label-mono uppercase tracking-widest text-primary">Admin analytics</p>
        <h1 class="mt-xs w-full font-display text-headline-md font-bold text-on-surface">Tổng quan Watchora</h1>
        <p class="mt-xs w-full text-body-sm text-on-surface-variant">Theo dõi catalog, đơn hàng, doanh thu và tồn kho cần xử lý.</p>
      </div>
      <button class="watch-admin-button shrink-0 cursor-pointer px-md py-2 text-body-sm text-on-surface transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-60" type="button" :disabled="loading" @click="fetchStats">
        Làm mới
      </button>
    </div>

    <p v-if="error" class="mt-md w-full rounded-lg border border-error/40 bg-error/10 p-md text-body-sm text-error">{{ error }}</p>

    <div v-if="loading" class="mt-lg grid w-full grid-cols-1 gap-md sm:grid-cols-2 xl:grid-cols-5">
      <div v-for="index in 5" :key="index" class="h-36 animate-pulse rounded-lg border border-surface-variant bg-surface"></div>
    </div>

    <template v-else-if="stats">
      <section class="mt-lg grid w-full grid-cols-1 gap-md sm:grid-cols-2 xl:grid-cols-5" aria-label="System statistics">
        <DashboardStatCard label="Tổng sản phẩm" :value="stats.total_products" icon="inventory_2" :detail="`${productCounts.active ?? 0} active · ${productCounts.draft ?? 0} draft · ${productCounts.inactive ?? 0} inactive`" />
        <DashboardStatCard label="Tổng đơn hàng" :value="stats.total_orders" icon="receipt_long" :detail="`${orderCounts.paid ?? 0} paid · ${orderCounts.shipping ?? 0} shipping · ${orderCounts.completed ?? 0} completed`" />
        <DashboardStatCard label="Cần xử lý" :value="stats.needs_attention_count" icon="priority_high" detail="Đơn paid_stock_issue" />
        <DashboardStatCard label="Doanh thu" :value="formatCurrency(stats.total_revenue)" icon="payments" detail="Paid, shipping, completed" />
        <DashboardStatCard label="Variant sắp hết" :value="stats.low_stock_variants_count" icon="inventory" :detail="`Ngưỡng <= ${stats.low_stock_threshold}`" />
      </section>

      <section class="mt-lg grid w-full min-w-0 grid-cols-1 gap-md xl:grid-cols-3">
        <article class="watch-admin-card w-full min-w-0 p-md xl:col-span-2">
          <div class="w-full min-w-0">
            <p class="w-full font-mono text-[11px] uppercase tracking-widest text-primary">7 ngày gần nhất</p>
            <h2 class="mt-xs w-full font-display text-headline-sm font-semibold text-on-surface">Doanh thu đã thanh toán</h2>
          </div>
          <RevenueBarChart class="mt-md" :data="stats.revenue_last_7_days" />
        </article>

        <article class="watch-admin-card w-full min-w-0 p-md">
          <div class="w-full min-w-0">
            <p class="w-full font-mono text-[11px] uppercase tracking-widest text-primary">Order status</p>
            <h2 class="mt-xs w-full font-display text-headline-sm font-semibold text-on-surface">Trạng thái đơn hàng</h2>
          </div>
          <div class="mt-md w-full min-w-0 overflow-hidden rounded-lg border border-surface-variant">
            <table class="w-full min-w-[360px] text-left text-body-sm">
              <thead class="bg-surface-container-lowest text-on-surface-variant">
                <tr><th class="px-sm py-3 font-medium">Trạng thái</th><th class="px-sm py-3 text-right font-medium">Số lượng</th></tr>
              </thead>
              <tbody>
                <tr v-for="(count, status) in orderCounts" :key="status" class="border-t border-surface-variant transition-colors hover:bg-surface-container-highest/40" :class="{ 'bg-error/5': status === 'paid_stock_issue' }">
                  <td class="px-sm py-3 text-on-surface"><RouterLink :to="`/admin/orders?status=${status}`" class="inline-flex rounded px-2 py-1 font-mono text-[11px] uppercase" :class="status === 'paid_stock_issue' ? 'bg-error/10 text-error' : 'bg-surface-container-highest text-on-surface-variant'">{{ status }}</RouterLink></td>
                  <td class="px-sm py-3 text-right font-mono text-lg font-semibold" :class="status === 'paid_stock_issue' ? 'text-error' : 'text-primary'">{{ count }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <RouterLink to="/admin/orders?status=paid_stock_issue" class="mt-md inline-flex items-center gap-xs text-body-sm text-error hover:underline">Mở đơn cần xử lý <span class="material-symbols-outlined text-[18px]">arrow_forward</span></RouterLink>
        </article>
      </section>

      <section class="watch-admin-card mt-lg w-full min-w-0 overflow-hidden">
        <div class="w-full min-w-0 border-b border-surface-variant p-md">
          <p class="w-full font-mono text-[11px] uppercase tracking-widest text-primary">Top selling</p>
          <h2 class="mt-xs w-full font-display text-headline-sm font-semibold text-on-surface">Top 5 sản phẩm bán chạy</h2>
        </div>
        <div class="w-full min-w-0 overflow-x-auto">
          <table class="w-full min-w-[720px] text-left text-body-sm">
            <thead class="bg-surface-container-lowest text-on-surface-variant">
              <tr><th class="px-md py-sm font-medium">Sản phẩm</th><th class="px-md py-sm font-medium">Brand</th><th class="px-md py-sm text-right font-medium">Số lượng</th><th class="px-md py-sm text-right font-medium">Doanh thu</th></tr>
            </thead>
            <tbody>
              <tr v-if="!stats.top_selling_products?.length"><td colspan="4" class="px-md py-lg text-center text-on-surface-variant">Chưa có sản phẩm bán chạy.</td></tr>
              <tr v-for="product in stats.top_selling_products" :key="product.product_id" class="border-t border-surface-variant hover:bg-surface-container-highest/40">
                <td class="px-md py-sm font-medium text-on-surface">{{ product.product_name }}</td>
                <td class="px-md py-sm text-on-surface-variant">{{ product.brand_name || '—' }}</td>
                <td class="px-md py-sm text-right font-mono text-primary">{{ product.total_quantity }}</td>
                <td class="px-md py-sm text-right font-mono font-semibold text-on-surface">{{ formatCurrency(product.total_revenue) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </template>
  </main>
</template>
