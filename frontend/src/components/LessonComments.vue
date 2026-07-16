<script setup>
import { computed, ref, watch } from 'vue'
import { createLessonComment, deleteLessonComment, getLessonComments } from '../services/api'
import { useAuthStore } from '../stores/auth'
import LessonCommentItem from './LessonCommentItem.vue'

const props = defineProps({
  lessonId: { type: [Number, String], required: true },
})

const authStore = useAuthStore()
const comments = ref([])
const content = ref('')
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(true)
const loadingMore = ref(false)
const submitting = ref(false)
const deletingId = ref(null)
const error = ref('')
const success = ref('')

const canLoadMore = computed(() => pagination.value.current_page < pagination.value.last_page)

const fetchComments = async ({ append = false, page = 1 } = {}) => {
  if (append) loadingMore.value = true
  else loading.value = true
  error.value = ''
  try {
    const response = await getLessonComments(props.lessonId, { page, per_page: 10 })
    const data = response.data.data
    comments.value = append ? [...comments.value, ...(data.items ?? [])] : (data.items ?? [])
    pagination.value = data.pagination ?? pagination.value
  } catch (requestError) {
    error.value = requestError.response?.data?.message ?? 'Không thể tải bình luận.'
  } finally {
    loading.value = false
    loadingMore.value = false
  }
}

const createComment = async (payload) => {
  submitting.value = true
  error.value = ''
  success.value = ''
  try {
    await createLessonComment(props.lessonId, payload)
    content.value = ''
    success.value = 'Bình luận đã được gửi.'
    await fetchComments()
  } catch (requestError) {
    const validationErrors = requestError.response?.data?.data?.errors
    error.value = validationErrors
      ? Object.values(validationErrors).flat()[0]
      : (requestError.response?.data?.message ?? 'Không thể gửi bình luận.')
  } finally {
    submitting.value = false
  }
}

const submitRootComment = () => {
  const value = content.value.trim()
  if (!value || submitting.value) return
  createComment({ content: value })
}

const submitReply = ({ parentId, content: replyContent }) => {
  if (submitting.value) return
  createComment({ content: replyContent, parent_id: parentId })
}

const removeComment = async (comment) => {
  if (!window.confirm('Bạn chắc chắn muốn xóa bình luận này?')) return
  deletingId.value = comment.id
  error.value = ''
  try {
    await deleteLessonComment(comment.id)
    await fetchComments()
  } catch (requestError) {
    error.value = requestError.response?.data?.message ?? 'Không thể xóa bình luận.'
  } finally {
    deletingId.value = null
  }
}

watch(() => props.lessonId, () => fetchComments(), { immediate: true })
</script>

<template>
  <section class="w-full min-w-0 rounded-lg border border-surface-variant bg-surface p-md">
    <div class="w-full min-w-0"><p class="w-full font-mono text-label-mono uppercase tracking-widest text-primary">Discussion</p><h2 class="mt-xs w-full font-display text-headline-sm font-semibold text-on-surface">Bình luận bài học</h2><p class="mt-xs w-full text-body-sm text-on-surface-variant">{{ pagination.total }} bình luận gốc</p></div>

    <form class="mt-md w-full min-w-0" @submit.prevent="submitRootComment">
      <textarea v-model="content" class="w-full min-w-0 rounded-lg border border-surface-variant bg-background px-md py-3 text-body-sm text-on-surface outline-none placeholder:text-on-surface-variant focus:border-primary" rows="3" maxlength="5000" placeholder="Đặt câu hỏi hoặc chia sẻ về bài học..."></textarea>
      <p v-if="error" class="mt-xs w-full text-body-sm text-error">{{ error }}</p>
      <p v-if="success" class="mt-xs w-full text-body-sm text-[var(--accent-success)]">{{ success }}</p>
      <button class="mt-sm w-full rounded-lg bg-primary px-md py-3 font-display text-button-text font-semibold text-white disabled:opacity-50 sm:w-auto" type="submit" :disabled="submitting || !content.trim()">{{ submitting ? 'Đang gửi...' : 'Gửi bình luận' }}</button>
    </form>

    <div v-if="loading" class="mt-md space-y-sm"><div v-for="index in 3" :key="index" class="h-24 w-full animate-pulse rounded-lg bg-surface-container-highest"></div></div>
    <div v-else-if="!comments.length" class="mt-md flex min-h-40 w-full min-w-0 items-center justify-center rounded-lg border border-dashed border-surface-variant text-center"><div class="w-full min-w-0 max-w-md p-md"><span class="material-symbols-outlined text-5xl text-primary">forum</span><p class="mt-sm w-full text-body-md text-on-surface">Chưa có bình luận nào.</p></div></div>
    <div v-else class="mt-md w-full min-w-0 space-y-sm" :class="{ 'pointer-events-none opacity-60': deletingId }"><LessonCommentItem v-for="comment in comments" :key="comment.id" :comment="comment" :current-user="authStore.user" @reply="submitReply" @delete="removeComment" /></div>

    <button v-if="canLoadMore" class="mt-md w-full rounded-lg border border-surface-variant px-md py-3 text-body-sm text-on-surface hover:border-primary disabled:opacity-50" type="button" :disabled="loadingMore" @click="fetchComments({ append: true, page: pagination.current_page + 1 })">{{ loadingMore ? 'Đang tải...' : 'Xem thêm bình luận' }}</button>
  </section>
</template>
