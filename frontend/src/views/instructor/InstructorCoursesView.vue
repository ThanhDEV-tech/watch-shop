<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { deleteInstructorCourse, getInstructorCourses, submitInstructorCourse } from '../../services/api'
import { formatCurrency } from '../../utils/formatCurrency'

const route = useRoute()
const courses = ref([])
const loading = ref(true)
const error = ref('')
const submittingId = ref(null)
const deletingId = ref(null)
const blockedCourseId = ref(null)
const notice = computed(() => String(route.query.notice ?? ''))

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
    const response = await getInstructorCourses()
    courses.value = response.data.data ?? []
  } catch (requestError) {
    error.value = requestError.response?.data?.message ?? 'Không thể tải danh sách khóa học.'
  } finally {
    loading.value = false
  }
}

const submitCourse = async (course) => {
  submittingId.value = course.id
  blockedCourseId.value = null
  error.value = ''
  try {
    const response = await submitInstructorCourse(course.id)
    Object.assign(course, response.data.data)
  } catch (requestError) {
    error.value = requestError.response?.data?.message ?? 'Không thể gửi duyệt khóa học.'
    if (error.value.includes('ít nhất 1 chương')) blockedCourseId.value = course.id
  } finally {
    submittingId.value = null
  }
}

const removeCourse = async (course) => {
  if (!window.confirm(`Bạn chắc chắn muốn xóa khóa học “${course.title}”?`)) return
  deletingId.value = course.id
  error.value = ''
  try {
    await deleteInstructorCourse(course.id)
    courses.value = courses.value.filter((item) => item.id !== course.id)
  } catch (requestError) {
    error.value = requestError.response?.data?.message ?? 'Không thể xóa khóa học.'
  } finally {
    deletingId.value = null
  }
}

onMounted(fetchCourses)
</script>

<template>
  <main class="mx-auto w-full min-w-0 max-w-[1440px] p-margin-mobile md:p-gutter lg:p-lg">
    <div class="flex w-full min-w-0 flex-wrap items-end justify-between gap-md">
      <div class="w-full min-w-0 sm:w-auto"><p class="w-full font-mono text-label-mono uppercase tracking-widest text-primary">Instructor workspace</p><h1 class="mt-xs w-full font-display text-headline-md font-bold text-on-surface">Khóa học của tôi</h1><p class="mt-xs w-full text-body-sm text-on-surface-variant">Tạo nội dung, gửi duyệt và theo dõi học viên.</p></div>
      <RouterLink to="/instructor/courses/create" class="w-full rounded-lg bg-primary-container px-md py-3 text-center font-display text-body-sm font-semibold text-on-primary-container hover:opacity-90 sm:w-auto">Tạo khóa học mới</RouterLink>
    </div>

    <p v-if="notice" class="mt-md w-full rounded-lg border border-[var(--accent-success)]/40 bg-[var(--accent-success)]/10 p-md text-body-sm text-[var(--accent-success)]">{{ notice }}</p>
    <div v-if="error" class="mt-md w-full min-w-0 rounded-lg border border-error/40 bg-error/10 p-md text-body-sm text-error"><p class="w-full">{{ error }}</p><RouterLink v-if="blockedCourseId" :to="`/instructor/courses/${blockedCourseId}/curriculum`" class="mt-sm inline-flex items-center gap-xs text-primary hover:underline">Quản lý nội dung ngay <span class="material-symbols-outlined text-[18px]">arrow_forward</span></RouterLink></div>

    <section class="mt-lg w-full min-w-0 overflow-hidden rounded-lg border border-surface-variant bg-surface">
      <div class="w-full min-w-0 overflow-x-auto">
        <table class="w-full min-w-[1180px] text-left text-body-sm">
          <thead class="bg-surface-container-lowest text-on-surface-variant"><tr><th class="px-md py-sm font-medium">Khóa học</th><th class="px-md py-sm font-medium">Category</th><th class="px-md py-sm font-medium">Giá</th><th class="px-md py-sm font-medium">Trạng thái</th><th class="px-md py-sm font-medium">Học viên</th><th class="px-md py-sm text-right font-medium">Thao tác</th></tr></thead>
          <tbody>
            <tr v-if="loading"><td colspan="6" class="px-md py-xl text-center text-on-surface-variant"><span class="material-symbols-outlined animate-spin align-middle text-primary">progress_activity</span> Đang tải khóa học...</td></tr>
            <tr v-else-if="!courses.length"><td colspan="6" class="px-md py-xl text-center text-on-surface-variant"><p class="w-full">Bạn chưa có khóa học nào.</p><RouterLink to="/instructor/courses/create" class="mt-sm inline-block text-primary hover:underline">Tạo khóa học đầu tiên</RouterLink></td></tr>
            <tr v-for="course in courses" v-else :key="course.id" class="border-t border-surface-variant align-top hover:bg-surface-container-highest/40">
              <td class="px-md py-sm"><div class="flex w-full min-w-0 items-start gap-sm"><img v-if="course.thumbnail_url" :src="course.thumbnail_url" :alt="course.title" class="h-14 w-24 shrink-0 rounded object-cover" /><div v-else class="flex h-14 w-24 shrink-0 items-center justify-center rounded bg-surface-container-lowest"><span class="material-symbols-outlined text-on-surface-variant">image</span></div><div class="w-full min-w-0"><p class="w-full font-medium text-on-surface">{{ course.title }}</p><p v-if="course.status === 'rejected' && course.reject_reason" class="mt-xs w-full text-xs text-error">{{ course.reject_reason }}</p></div></div></td>
              <td class="px-md py-sm text-on-surface-variant">{{ course.category?.name }}</td><td class="px-md py-sm font-mono text-on-surface">{{ formatCurrency(course.final_price) }}</td>
              <td class="px-md py-sm"><span class="rounded px-2 py-1 font-mono text-[11px] uppercase" :class="statusClasses[course.status]">{{ course.status }}</span></td><td class="px-md py-sm font-mono text-on-surface">{{ course.total_students }}</td>
              <td class="px-md py-sm"><div class="flex w-full min-w-0 flex-wrap justify-end gap-xs"><button v-if="['draft', 'rejected'].includes(course.status)" class="rounded-lg border border-tertiary/40 px-sm py-2 text-xs font-medium text-tertiary hover:bg-tertiary/10 disabled:opacity-50" type="button" :disabled="submittingId === course.id" @click="submitCourse(course)">{{ submittingId === course.id ? 'Đang gửi...' : 'Gửi duyệt' }}</button><RouterLink :to="`/instructor/courses/${course.id}/preview`" target="_blank" rel="noopener" class="rounded-lg border border-primary/40 px-sm py-2 text-xs text-primary hover:bg-primary/10">Xem trước</RouterLink><RouterLink :to="`/instructor/courses/${course.id}/curriculum`" class="rounded-lg border border-surface-variant px-sm py-2 text-xs text-on-surface hover:border-primary">Quản lý nội dung</RouterLink><RouterLink :to="`/instructor/courses/${course.id}/students`" class="rounded-lg border border-surface-variant px-sm py-2 text-xs text-on-surface hover:border-primary">Xem học viên</RouterLink><RouterLink :to="`/instructor/courses/${course.id}/edit`" class="rounded-lg border border-surface-variant px-sm py-2 text-xs text-on-surface hover:border-primary">Sửa</RouterLink><button class="rounded-lg border border-error/30 px-sm py-2 text-xs text-error hover:bg-error/10 disabled:opacity-50" type="button" :disabled="deletingId === course.id" @click="removeCourse(course)">{{ deletingId === course.id ? 'Đang xóa...' : 'Xóa' }}</button></div></td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</template>
