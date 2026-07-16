<script setup>
import { ref, watch } from 'vue'
import { formatCurrency } from '../../utils/formatCurrency'
import BaseModal from '../ui/BaseModal.vue'

const props = defineProps({
  course: { type: Object, default: null },
  loading: { type: Boolean, default: false },
  actionLoading: { type: Boolean, default: false },
  error: { type: String, default: '' },
})

const emit = defineEmits(['close', 'approve', 'reject'])
const showRejectForm = ref(false)
const reason = ref('')
const reasonError = ref('')

watch(() => props.course?.id, () => {
  showRejectForm.value = false
  reason.value = ''
  reasonError.value = ''
})

const submitReject = () => {
  if (!reason.value.trim()) {
    reasonError.value = 'Vui lòng nhập lý do từ chối.'
    return
  }

  reasonError.value = ''
  emit('reject', reason.value.trim())
}
</script>

<template>
  <BaseModal max-width="wide" aria-labelledby="course-review-title" @close="emit('close')">
      <section class="flex max-h-[92vh] w-full min-w-0 flex-col overflow-hidden rounded-xl border border-surface-variant bg-background shadow-2xl">
        <header class="flex w-full min-w-0 items-center justify-between gap-md border-b border-surface-variant bg-surface px-md py-sm">
          <div class="w-full min-w-0">
            <p class="w-full font-mono text-[11px] uppercase tracking-widest text-primary">Course review</p>
            <h2 id="course-review-title" class="w-full truncate font-display text-headline-sm font-semibold text-on-surface">{{ course?.title ?? 'Đang tải khóa học...' }}</h2>
          </div>
          <button class="material-symbols-outlined shrink-0 rounded-lg p-2 text-on-surface-variant hover:bg-surface-container-highest hover:text-on-surface" type="button" aria-label="Đóng" @click="emit('close')">close</button>
        </header>

        <div v-if="loading" class="flex min-h-[420px] w-full min-w-0 items-center justify-center text-on-surface-variant"><span class="material-symbols-outlined animate-spin text-primary">progress_activity</span>&nbsp; Đang tải nội dung...</div>

        <div v-else-if="course" class="w-full min-w-0 flex-1 overflow-y-auto p-md md:p-lg">
          <div class="grid w-full min-w-0 grid-cols-1 gap-md lg:grid-cols-3">
            <div class="w-full min-w-0 lg:col-span-2">
              <img v-if="course.thumbnail_url" :src="course.thumbnail_url" :alt="course.title" class="aspect-video w-full rounded-lg border border-surface-variant object-cover" />
              <div v-else class="flex aspect-video w-full min-w-0 items-center justify-center rounded-lg border border-surface-variant bg-surface"><span class="material-symbols-outlined text-6xl text-primary">image</span></div>
            </div>
            <div class="w-full min-w-0 rounded-lg border border-surface-variant bg-surface p-md">
              <p class="w-full text-body-sm text-on-surface-variant">Giảng viên</p><p class="mt-xs w-full font-medium text-on-surface">{{ course.instructor?.name }}</p><p class="w-full text-xs text-on-surface-variant">{{ course.instructor?.email }}</p>
              <p class="mt-md w-full text-body-sm text-on-surface-variant">Danh mục</p><p class="mt-xs w-full text-on-surface">{{ course.category?.name }}</p>
              <p class="mt-md w-full text-body-sm text-on-surface-variant">Mức độ · Giá</p><p class="mt-xs w-full font-mono text-on-surface">{{ course.level }} · {{ formatCurrency(course.final_price) }}</p>
              <p class="mt-md w-full text-body-sm text-on-surface-variant">Trạng thái</p><p class="mt-xs w-full font-mono uppercase text-primary">{{ course.status }}</p>
            </div>
          </div>

          <div class="mt-lg w-full min-w-0">
            <h3 class="w-full font-display text-lg font-semibold text-on-surface">Mô tả</h3>
            <p class="mt-sm w-full whitespace-pre-line text-body-sm leading-6 text-on-surface-variant">{{ course.description || 'Chưa có mô tả.' }}</p>
            <h3 class="mt-md w-full font-display text-lg font-semibold text-on-surface">Nội dung chi tiết</h3>
            <p class="mt-sm w-full whitespace-pre-line text-body-sm leading-6 text-on-surface-variant">{{ course.content || 'Chưa có nội dung chi tiết.' }}</p>
          </div>

          <div class="mt-lg w-full min-w-0">
            <h3 class="w-full font-display text-lg font-semibold text-on-surface">Chương trình học</h3>
            <div v-if="course.chapters?.length" class="mt-sm w-full min-w-0 space-y-sm">
              <article v-for="chapter in course.chapters" :key="chapter.id" class="w-full min-w-0 rounded-lg border border-surface-variant bg-surface p-md">
                <p class="w-full font-display font-semibold text-on-surface">Chapter {{ chapter.position }}: {{ chapter.title }}</p>
                <p v-if="chapter.description" class="mt-xs w-full text-body-sm text-on-surface-variant">{{ chapter.description }}</p>
                <ul class="mt-sm w-full min-w-0 divide-y divide-surface-variant">
                  <li v-for="lesson in chapter.lessons" :key="lesson.id" class="flex w-full min-w-0 items-start gap-sm py-sm text-body-sm">
                    <span class="material-symbols-outlined shrink-0 text-[18px] text-primary">play_circle</span>
                    <div class="w-full min-w-0"><p class="w-full text-on-surface">{{ lesson.title }}</p><p class="w-full font-mono text-[11px] text-on-surface-variant">{{ lesson.duration_seconds }} giây</p></div>
                  </li>
                </ul>
              </article>
            </div>
            <p v-else class="mt-sm w-full rounded-lg border border-surface-variant bg-surface p-md text-body-sm text-on-surface-variant">Khóa học chưa có chapter hoặc lesson.</p>
          </div>

          <p v-if="course.reject_reason" class="mt-md w-full rounded-lg border border-error/30 bg-error/10 p-md text-body-sm text-error"><strong>Lý do từ chối:</strong> {{ course.reject_reason }}</p>
          <p v-if="error" class="mt-md w-full rounded-lg border border-error/40 bg-error/10 p-md text-body-sm text-error">{{ error }}</p>

          <div v-if="course.status === 'pending'" class="mt-lg w-full min-w-0 border-t border-surface-variant pt-md">
            <div v-if="showRejectForm" class="w-full min-w-0">
              <label class="block w-full text-body-sm font-medium text-on-surface" for="reject-reason">Lý do từ chối</label>
              <textarea id="reject-reason" v-model="reason" rows="3" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-sm text-body-sm text-on-surface outline-none focus:border-primary" placeholder="Nêu rõ nội dung giảng viên cần chỉnh sửa..."></textarea>
              <p v-if="reasonError" class="mt-xs w-full text-xs text-error">{{ reasonError }}</p>
            </div>
            <div class="mt-sm flex w-full min-w-0 flex-col gap-sm sm:flex-row sm:justify-end">
              <button v-if="showRejectForm" class="w-full rounded-lg border border-surface-variant px-md py-3 text-body-sm text-on-surface sm:w-auto" type="button" :disabled="actionLoading" @click="showRejectForm = false">Hủy từ chối</button>
              <button class="w-full rounded-lg border border-error/50 px-md py-3 font-display text-body-sm font-semibold text-error hover:bg-error/10 disabled:opacity-50 sm:w-auto" type="button" :disabled="actionLoading" @click="showRejectForm ? submitReject() : showRejectForm = true">{{ actionLoading && showRejectForm ? 'Đang từ chối...' : 'Từ chối' }}</button>
              <button v-if="!showRejectForm" class="w-full rounded-lg bg-primary-container px-md py-3 font-display text-body-sm font-semibold text-on-primary-container hover:opacity-90 disabled:opacity-50 sm:w-auto" type="button" :disabled="actionLoading" @click="emit('approve')">{{ actionLoading ? 'Đang duyệt...' : 'Duyệt' }}</button>
            </div>
          </div>
          <p v-else class="mt-lg w-full rounded-lg border border-surface-variant bg-surface p-md text-center text-body-sm text-on-surface-variant">Khóa học đã được xử lý. Không thể duyệt lại.</p>
        </div>
      </section>
  </BaseModal>
</template>
