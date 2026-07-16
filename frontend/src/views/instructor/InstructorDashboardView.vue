<script setup>
import { onMounted, ref } from 'vue'
import DashboardStatCard from '../../components/dashboard/DashboardStatCard.vue'
import { getInstructorDashboardStats } from '../../services/api'
import { formatCurrency } from '../../utils/formatCurrency'

const stats = ref(null)
const loading = ref(true)
const error = ref('')

const fetchStats = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await getInstructorDashboardStats()
    stats.value = response.data.data
  } catch (requestError) {
    error.value = requestError.response?.data?.message ?? 'Không thể tải dữ liệu giảng viên.'
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
        <p class="w-full font-mono text-label-mono uppercase tracking-widest text-primary">Instructor analytics</p>
        <h1 class="mt-xs w-full font-display text-headline-md font-bold text-on-surface">Kênh giảng dạy</h1>
        <p class="mt-xs w-full text-body-sm text-on-surface-variant">Hiệu suất khóa học và học viên của riêng bạn.</p>
      </div>
      <button class="shrink-0 cursor-pointer rounded-lg border border-surface-variant px-md py-2 text-body-sm text-on-surface hover:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-60" type="button" :disabled="loading" @click="fetchStats">Làm mới</button>
    </div>

    <p v-if="error" class="mt-md w-full rounded-lg border border-error/40 bg-error/10 p-md text-body-sm text-error">{{ error }}</p>
    <div v-if="loading" class="mt-lg grid w-full grid-cols-1 gap-md md:grid-cols-3"><div v-for="index in 3" :key="index" class="h-36 animate-pulse rounded-lg border border-surface-variant bg-surface"></div></div>

    <template v-else-if="stats">
      <section class="mt-lg grid w-full grid-cols-1 gap-md md:grid-cols-3" aria-label="Instructor statistics">
        <DashboardStatCard label="Khóa học của tôi" :value="stats.total_courses" icon="menu_book" detail="Tất cả trạng thái" />
        <DashboardStatCard label="Lượt ghi danh" :value="stats.total_students" icon="groups" detail="Tổng enrollment trên các course" />
        <DashboardStatCard label="Doanh thu" :value="formatCurrency(stats.total_revenue)" icon="payments" detail="Chỉ tính đơn hàng đã paid" />
      </section>

      <section class="mt-lg w-full min-w-0 overflow-hidden rounded-lg border border-surface-variant bg-surface">
        <div class="w-full min-w-0 border-b border-surface-variant p-md">
          <p class="w-full font-mono text-[11px] uppercase tracking-widest text-primary">Course performance</p>
          <h2 class="mt-xs w-full font-display text-headline-sm font-semibold text-on-surface">Hiệu suất từng khóa học</h2>
        </div>
        <div class="w-full min-w-0 overflow-x-auto">
          <table class="w-full min-w-[760px] text-left text-body-sm">
            <thead class="bg-surface-container-lowest text-on-surface-variant"><tr><th class="px-md py-sm font-medium">Khóa học</th><th class="px-md py-sm font-medium">Trạng thái</th><th class="px-md py-sm font-medium">Học viên</th><th class="px-md py-sm font-medium">Doanh thu</th><th class="px-md py-sm text-right font-medium">Chi tiết</th></tr></thead>
            <tbody>
              <tr v-if="!stats.courses_performance?.length"><td colspan="5" class="px-md py-xl text-center text-on-surface-variant">Bạn chưa có khóa học nào để thống kê.</td></tr>
              <tr v-for="course in stats.courses_performance" v-else :key="course.id" class="border-t border-surface-variant hover:bg-surface-container-highest/40">
                <td class="max-w-md px-md py-sm"><p class="w-full min-w-0 font-medium text-on-surface">{{ course.title }}</p></td>
                <td class="px-md py-sm"><span class="rounded border border-surface-variant px-2 py-1 font-mono text-[11px] uppercase text-primary">{{ course.status }}</span></td>
                <td class="px-md py-sm font-mono text-on-surface">{{ course.total_students }}</td>
                <td class="px-md py-sm font-mono font-semibold text-on-surface">{{ formatCurrency(course.revenue) }}</td>
                <td class="px-md py-sm text-right"><RouterLink :to="`/instructor/courses/${course.id}/students`" class="cursor-pointer rounded text-primary hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background">Xem học viên</RouterLink></td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </template>
  </main>
</template>
