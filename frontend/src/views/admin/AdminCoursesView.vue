<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import CourseReviewModal from '../../components/dashboard/CourseReviewModal.vue'
import DashboardPagination from '../../components/dashboard/DashboardPagination.vue'
import { approveAdminCourse, getAdminCourse, getAdminCourses, rejectAdminCourse } from '../../services/api'
import { formatDate } from '../../utils/formatDate'

const route = useRoute()
const router = useRouter()
const courses = ref([])
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(true)
const error = ref('')
const selectedCourse = ref(null)
const detailLoading = ref(false)
const actionLoading = ref(false)
const modalError = ref('')
const filters = reactive({ search: '', status: String(route.query.status ?? ''), page: 1 })

const statusClasses = {
  draft: 'bg-surface-container-highest text-on-surface-variant',
  pending: 'bg-tertiary/10 text-tertiary',
  approved: 'bg-[var(--accent-success)]/10 text-[var(--accent-success)]',
  rejected: 'bg-error/10 text-error',
}

const fetchCourses = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await getAdminCourses({ ...filters, per_page: 10 })
    courses.value = response.data.data.items ?? []
    pagination.value = response.data.data.pagination
  } catch (requestError) {
    error.value = requestError.response?.data?.message ?? 'Không thể tải danh sách khóa học.'
  } finally {
    loading.value = false
  }
}

const applyFilters = async () => {
  filters.page = 1
  await router.replace({ query: filters.status ? { status: filters.status } : {} })
  fetchCourses()
}

const changePage = (page) => {
  filters.page = page
  fetchCourses()
}

const openCourse = async (course) => {
  selectedCourse.value = course
  detailLoading.value = true
  modalError.value = ''
  try {
    const response = await getAdminCourse(course.id)
    selectedCourse.value = response.data.data
  } catch (requestError) {
    modalError.value = requestError.response?.data?.message ?? 'Không thể tải chi tiết khóa học.'
  } finally {
    detailLoading.value = false
  }
}

const syncUpdatedCourse = (updated) => {
  const row = courses.value.find((course) => course.id === updated.id)
  if (row) Object.assign(row, updated)
  Object.assign(selectedCourse.value, updated)
}

const approveCourse = async () => {
  actionLoading.value = true
  modalError.value = ''
  try {
    const response = await approveAdminCourse(selectedCourse.value.id)
    syncUpdatedCourse(response.data.data)
  } catch (requestError) {
    modalError.value = requestError.response?.data?.message ?? 'Không thể duyệt khóa học.'
  } finally {
    actionLoading.value = false
  }
}

const rejectCourse = async (reason) => {
  actionLoading.value = true
  modalError.value = ''
  try {
    const response = await rejectAdminCourse(selectedCourse.value.id, reason)
    syncUpdatedCourse(response.data.data)
  } catch (requestError) {
    modalError.value = requestError.response?.data?.message ?? 'Không thể từ chối khóa học.'
  } finally {
    actionLoading.value = false
  }
}

onMounted(fetchCourses)
</script>

<template>
  <main class="mx-auto w-full min-w-0 max-w-[1440px] p-margin-mobile md:p-gutter lg:p-lg">
    <div class="w-full min-w-0">
      <p class="w-full font-mono text-label-mono uppercase tracking-widest text-primary">Course moderation</p>
      <h1 class="mt-xs w-full font-display text-headline-md font-bold text-on-surface">Duyệt khóa học</h1>
      <p class="mt-xs w-full text-body-sm text-on-surface-variant">Xem đầy đủ nội dung trước khi duyệt hoặc từ chối khóa học.</p>
    </div>

    <form class="mt-lg flex w-full min-w-0 flex-col gap-sm rounded-lg border border-surface-variant bg-surface p-md md:flex-row" @submit.prevent="applyFilters">
      <input v-model.trim="filters.search" class="w-full min-w-0 flex-1 rounded-lg border border-surface-variant bg-background px-md py-3 text-body-sm text-on-surface outline-none focus:border-primary" placeholder="Tìm tên course hoặc instructor..." />
      <select v-model="filters.status" class="w-full rounded-lg border border-surface-variant bg-background px-md py-3 text-body-sm text-on-surface outline-none focus:border-primary md:w-52">
        <option value="">Tất cả trạng thái</option><option value="draft">Draft</option><option value="pending">Pending</option><option value="approved">Approved</option><option value="rejected">Rejected</option>
      </select>
      <button class="w-full rounded-lg bg-primary-container px-md py-3 font-display text-body-sm font-semibold text-on-primary-container hover:opacity-90 md:w-auto" type="submit">Áp dụng</button>
    </form>

    <p v-if="error" class="mt-md w-full rounded-lg border border-error/40 bg-error/10 p-md text-body-sm text-error">{{ error }}</p>
    <section class="mt-md w-full min-w-0 overflow-hidden rounded-lg border border-surface-variant bg-surface">
      <div class="w-full min-w-0 overflow-x-auto">
        <table class="w-full min-w-[980px] text-left text-body-sm">
          <thead class="bg-surface-container-lowest text-on-surface-variant"><tr><th class="px-md py-sm font-medium">Khóa học</th><th class="px-md py-sm font-medium">Instructor</th><th class="px-md py-sm font-medium">Category</th><th class="px-md py-sm font-medium">Trạng thái</th><th class="px-md py-sm font-medium">Ngày tạo</th><th class="px-md py-sm text-right font-medium">Review</th></tr></thead>
          <tbody>
            <tr v-if="loading"><td colspan="6" class="px-md py-xl text-center text-on-surface-variant"><span class="material-symbols-outlined animate-spin align-middle text-primary">progress_activity</span> Đang tải khóa học...</td></tr>
            <tr v-else-if="!courses.length"><td colspan="6" class="px-md py-xl text-center text-on-surface-variant">Không có khóa học phù hợp bộ lọc.</td></tr>
            <tr v-for="course in courses" v-else :key="course.id" class="cursor-pointer border-t border-surface-variant transition-colors hover:bg-surface-container-highest/40" tabindex="0" @click="openCourse(course)" @keydown.enter="openCourse(course)">
              <td class="px-md py-sm"><div class="flex w-full min-w-0 items-center gap-sm"><img v-if="course.thumbnail_url" :src="course.thumbnail_url" :alt="course.title" class="h-12 w-20 shrink-0 rounded object-cover" /><div v-else class="flex h-12 w-20 shrink-0 items-center justify-center rounded bg-surface-container-lowest"><span class="material-symbols-outlined text-on-surface-variant">image</span></div><p class="w-full min-w-0 font-medium text-on-surface">{{ course.title }}</p></div></td>
              <td class="px-md py-sm"><p class="w-full text-on-surface">{{ course.instructor?.name }}</p><p class="w-full text-xs text-on-surface-variant">{{ course.instructor?.email }}</p></td>
              <td class="px-md py-sm text-on-surface-variant">{{ course.category?.name }}</td>
              <td class="px-md py-sm"><span class="rounded px-2 py-1 font-mono text-[11px] uppercase" :class="statusClasses[course.status]">{{ course.status }}</span></td>
              <td class="px-md py-sm text-on-surface-variant">{{ formatDate(course.created_at) }}</td>
              <td class="px-md py-sm text-right text-primary">Xem chi tiết</td>
            </tr>
          </tbody>
        </table>
      </div>
      <DashboardPagination :pagination="pagination" @change="changePage" />
    </section>

    <CourseReviewModal v-if="selectedCourse" :course="selectedCourse" :loading="detailLoading" :action-loading="actionLoading" :error="modalError" @close="selectedCourse = null" @approve="approveCourse" @reject="rejectCourse" />
  </main>
</template>
