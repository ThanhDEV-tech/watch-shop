<script setup>
import { computed, onMounted, ref } from 'vue'
import DashboardStatCard from '../../components/dashboard/DashboardStatCard.vue'
import RevenueBarChart from '../../components/dashboard/RevenueBarChart.vue'
import { getAdminDashboardStats } from '../../services/api'
import { formatCurrency } from '../../utils/formatCurrency'

const stats = ref(null)
const loading = ref(true)
const error = ref('')

const courseBreakdown = computed(() => stats.value?.total_courses ?? {})

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
        <h1 class="mt-xs w-full font-display text-headline-md font-bold text-on-surface">Tổng quan hệ thống</h1>
        <p class="mt-xs w-full text-body-sm text-on-surface-variant">Theo dõi người dùng, khóa học và doanh thu EduMarket.</p>
      </div>
      <button class="shrink-0 cursor-pointer rounded-lg border border-surface-variant px-md py-2 text-body-sm text-on-surface transition-colors hover:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-60" type="button" :disabled="loading" @click="fetchStats">
        Làm mới
      </button>
    </div>

    <p v-if="error" class="mt-md w-full rounded-lg border border-error/40 bg-error/10 p-md text-body-sm text-error">{{ error }}</p>

    <div v-if="loading" class="mt-lg grid w-full grid-cols-1 gap-md sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="index in 4" :key="index" class="h-36 animate-pulse rounded-lg border border-surface-variant bg-surface"></div>
    </div>

    <template v-else-if="stats">
      <section class="mt-lg grid w-full grid-cols-1 gap-md sm:grid-cols-2 xl:grid-cols-4" aria-label="System statistics">
        <DashboardStatCard label="Tổng người dùng" :value="stats.total_users" icon="group" :detail="`${stats.total_students} học viên · ${stats.total_instructors} giảng viên`" />
        <DashboardStatCard label="Tổng khóa học" :value="courseBreakdown.all ?? 0" icon="menu_book" :detail="`${courseBreakdown.approved ?? 0} đã duyệt · ${courseBreakdown.rejected ?? 0} từ chối`" />
        <DashboardStatCard label="Tổng doanh thu" :value="formatCurrency(stats.total_revenue)" icon="payments" :detail="`${stats.total_orders} đơn hàng`" />
        <DashboardStatCard label="Chờ duyệt" :value="stats.pending_courses_count" icon="pending_actions" detail="Khóa học cần admin xử lý" />
      </section>

      <section class="mt-lg grid w-full min-w-0 grid-cols-1 gap-md xl:grid-cols-3">
        <article class="glass-card w-full min-w-0 rounded-lg p-md xl:col-span-2">
          <div class="w-full min-w-0">
            <p class="w-full font-mono text-[11px] uppercase tracking-widest text-primary">7 ngày gần nhất</p>
            <h2 class="mt-xs w-full font-display text-headline-sm font-semibold text-on-surface">Doanh thu đã thanh toán</h2>
          </div>
          <RevenueBarChart class="mt-md" :data="stats.revenue_last_7_days" />
        </article>

        <article id="pending-courses" class="glass-card w-full min-w-0 rounded-lg p-md">
          <div class="w-full min-w-0">
            <p class="w-full font-mono text-[11px] uppercase tracking-widest text-primary">Review queue</p>
            <h2 class="mt-xs w-full font-display text-headline-sm font-semibold text-on-surface">Course đang chờ duyệt</h2>
          </div>
          <div class="mt-md w-full min-w-0 overflow-hidden rounded-lg border border-surface-variant">
            <table class="w-full min-w-[320px] text-left text-body-sm">
              <thead class="bg-surface-container-lowest text-on-surface-variant">
                <tr><th class="px-sm py-3 font-medium">Trạng thái</th><th class="px-sm py-3 text-right font-medium">Số lượng</th></tr>
              </thead>
              <tbody>
                <tr class="border-t border-surface-variant transition-colors hover:bg-surface-container-highest/40">
                  <td class="p-0 text-on-surface"><RouterLink to="/admin/courses?status=pending" class="block w-full cursor-pointer px-sm py-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background"><span class="rounded bg-tertiary/10 px-2 py-1 text-tertiary">Pending</span></RouterLink></td>
                  <td class="p-0 text-right font-mono text-lg font-semibold text-primary"><RouterLink to="/admin/courses?status=pending" class="block w-full cursor-pointer px-sm py-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background">{{ stats.pending_courses_count }}</RouterLink></td>
                </tr>
              </tbody>
            </table>
          </div>
          <RouterLink to="/admin/courses?status=pending" class="mt-md inline-flex items-center gap-xs text-body-sm text-primary hover:underline">Mở hàng chờ duyệt <span class="material-symbols-outlined text-[18px]">arrow_forward</span></RouterLink>
        </article>
      </section>
    </template>
  </main>
</template>
