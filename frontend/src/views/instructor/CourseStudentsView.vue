<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import DashboardPagination from '../../components/dashboard/DashboardPagination.vue'
import { getCourseById, getInstructorCourseStudents } from '../../services/api'

const route = useRoute()
const students = ref([])
const course = ref(null)
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(true)
const error = ref('')
const dateFormatter = new Intl.DateTimeFormat('vi-VN', { dateStyle: 'medium' })
const formatDate = (value) => value ? dateFormatter.format(new Date(value)) : '—'

const fetchStudents = async (page = 1) => {
  loading.value = true
  error.value = ''
  try {
    const response = await getInstructorCourseStudents(route.params.id, { page, per_page: 10 })
    students.value = response.data.data.items ?? []
    pagination.value = response.data.data.pagination
  } catch (requestError) {
    error.value = requestError.response?.data?.message ?? 'Không thể tải danh sách học viên.'
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  const [, courseResult] = await Promise.allSettled([
    fetchStudents(),
    getCourseById(route.params.id),
  ])
  if (courseResult.status === 'fulfilled') course.value = courseResult.value.data.data
})
</script>

<template>
  <main class="mx-auto w-full min-w-0 max-w-[1440px] p-margin-mobile md:p-gutter lg:p-lg">
    <RouterLink to="/instructor/dashboard" class="inline-flex items-center gap-xs text-body-sm text-primary hover:underline"><span class="material-symbols-outlined text-[18px]">arrow_back</span> Tổng quan</RouterLink>
    <div class="mt-md w-full min-w-0">
      <p class="w-full font-mono text-label-mono uppercase tracking-widest text-primary">Course students</p>
      <h1 class="mt-xs w-full font-display text-headline-md font-bold text-on-surface">{{ course?.title ?? `Học viên khóa học #${route.params.id}` }}</h1>
      <p class="mt-xs w-full text-body-sm text-on-surface-variant">Theo dõi ngày ghi danh và tiến độ học tập.</p>
    </div>

    <p v-if="error" class="mt-md w-full rounded-lg border border-error/40 bg-error/10 p-md text-body-sm text-error">{{ error }}</p>
    <section class="mt-lg w-full min-w-0 overflow-hidden rounded-lg border border-surface-variant bg-surface">
      <div class="w-full min-w-0 overflow-x-auto">
        <table class="w-full min-w-[800px] text-left text-body-sm">
          <thead class="bg-surface-container-lowest text-on-surface-variant"><tr><th class="px-md py-sm font-medium">Học viên</th><th class="px-md py-sm font-medium">Email</th><th class="px-md py-sm font-medium">Ngày enroll</th><th class="px-md py-sm font-medium">Tiến độ</th><th class="px-md py-sm font-medium">Bài đã hoàn thành</th></tr></thead>
          <tbody>
            <tr v-if="loading"><td colspan="5" class="px-md py-xl text-center text-on-surface-variant"><span class="material-symbols-outlined animate-spin align-middle text-primary">progress_activity</span> Đang tải học viên...</td></tr>
            <tr v-else-if="!students.length"><td colspan="5" class="px-md py-xl text-center text-on-surface-variant">Khóa học này chưa có học viên enroll.</td></tr>
            <tr v-for="enrollment in students" v-else :key="enrollment.enrollment_id" class="border-t border-surface-variant hover:bg-surface-container-highest/40">
              <td class="px-md py-sm font-medium text-on-surface">{{ enrollment.student?.name }}</td><td class="px-md py-sm text-on-surface-variant">{{ enrollment.student?.email }}</td><td class="px-md py-sm text-on-surface-variant">{{ formatDate(enrollment.enrolled_at) }}</td>
              <td class="px-md py-sm"><div class="flex w-full min-w-0 items-center gap-sm"><div class="h-2 w-32 shrink-0 overflow-hidden rounded-full bg-surface-container-lowest"><div class="h-full rounded-full bg-[var(--accent-success)]" :style="{ width: `${enrollment.progress_percent}%` }"></div></div><span class="shrink-0 font-mono text-xs text-primary">{{ enrollment.progress_percent }}%</span></div></td>
              <td class="px-md py-sm font-mono text-on-surface">{{ enrollment.completed_lessons }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <DashboardPagination :pagination="pagination" @change="fetchStudents" />
    </section>
  </main>
</template>
