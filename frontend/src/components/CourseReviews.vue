<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { getCourseReviews, submitCourseReview } from '../services/api'
import { useAuthStore } from '../stores/auth'
import { formatDate } from '../utils/formatDate'

const props = defineProps({
  courseId: { type: [Number, String], required: true },
  ratingAverage: { type: [Number, String], default: 0 },
  isEnrolled: { type: Boolean, default: false },
})

const emit = defineEmits(['review-submitted'])
const authStore = useAuthStore()
const reviews = ref([])
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(true)
const loadingMore = ref(false)
const submitting = ref(false)
const error = ref('')
const success = ref('')
const form = reactive({ rating: 0, comment: '' })

const roundedAverage = computed(() => Math.round(Number(props.ratingAverage ?? 0)))
const canLoadMore = computed(() => pagination.value.current_page < pagination.value.last_page)
const canReview = computed(() => props.isEnrolled && authStore.isAuthenticated)

const applyExistingReview = (items) => {
  const ownReview = items.find((review) => review.user?.id === authStore.user?.id)
  if (!ownReview) return false

  form.rating = Number(ownReview.rating)
  form.comment = ownReview.comment ?? ''
  return true
}

const findExistingReview = async () => {
  if (!canReview.value || applyExistingReview(reviews.value)) return

  let page = 1
  while (true) {
    const response = await getCourseReviews(props.courseId, { page, per_page: 50 })
    const data = response.data.data
    if (applyExistingReview(data.items ?? [])) return
    const lastPage = data.pagination?.last_page ?? 1
    if (page >= lastPage) return
    page += 1
  }
}

const fetchReviews = async ({ append = false, page = 1 } = {}) => {
  if (append) loadingMore.value = true
  else loading.value = true
  error.value = ''

  try {
    const response = await getCourseReviews(props.courseId, { page, per_page: 6 })
    const data = response.data.data
    reviews.value = append ? [...reviews.value, ...(data.items ?? [])] : (data.items ?? [])
    pagination.value = data.pagination ?? pagination.value

    if (!append) await findExistingReview()
  } catch (requestError) {
    error.value = requestError.response?.data?.message ?? 'Không thể tải đánh giá khóa học.'
  } finally {
    loading.value = false
    loadingMore.value = false
  }
}

const loadMore = () => {
  if (!canLoadMore.value || loadingMore.value) return
  fetchReviews({ append: true, page: pagination.value.current_page + 1 })
}

const submitReview = async () => {
  if (!form.rating || submitting.value) {
    error.value = 'Vui lòng chọn số sao đánh giá.'
    return
  }

  submitting.value = true
  error.value = ''
  success.value = ''
  try {
    await submitCourseReview(props.courseId, {
      rating: form.rating,
      comment: form.comment.trim() || null,
    })
    success.value = 'Đánh giá của bạn đã được lưu.'
    await fetchReviews()
    emit('review-submitted')
  } catch (requestError) {
    const validationErrors = requestError.response?.data?.data?.errors
    error.value = validationErrors
      ? Object.values(validationErrors).flat()[0]
      : (requestError.response?.data?.message ?? 'Không thể gửi đánh giá.')
  } finally {
    submitting.value = false
  }
}

watch(() => props.courseId, () => fetchReviews(), { immediate: true })
</script>

<template>
  <section class="w-full min-w-0 space-y-md pb-xl">
    <div class="flex w-full min-w-0 flex-col gap-md sm:flex-row sm:items-end sm:justify-between">
      <div class="w-full min-w-0">
        <p class="w-full font-mono text-label-mono uppercase tracking-widest text-primary">Student feedback</p>
        <h2 class="mt-xs w-full font-display text-headline-sm font-semibold text-on-surface">Đánh giá khóa học</h2>
      </div>
      <div class="flex w-full min-w-0 items-center gap-sm sm:w-auto sm:justify-end">
        <span class="font-display text-headline-sm font-bold text-on-surface">{{ Number(ratingAverage).toFixed(1) }}</span>
        <div class="flex shrink-0 gap-0.5" aria-label="Điểm đánh giá trung bình">
          <span v-for="star in 5" :key="star" class="material-symbols-outlined text-[20px]" :class="star <= roundedAverage ? 'filled-star text-tertiary' : 'text-on-surface-variant'">star</span>
        </div>
        <span class="shrink-0 text-body-sm text-on-surface-variant">({{ pagination.total }} đánh giá)</span>
      </div>
    </div>

    <form v-if="canReview" class="w-full min-w-0 rounded-xl border border-surface-variant bg-surface p-md" @submit.prevent="submitReview">
      <div class="w-full min-w-0">
        <h3 class="w-full font-display text-body-md font-semibold text-on-surface">Đánh giá của bạn</h3>
        <p class="mt-xs w-full text-body-sm text-on-surface-variant">Bạn có thể gửi lại để cập nhật đánh giá trước đó.</p>
      </div>
      <div class="mt-md flex w-full min-w-0 items-center gap-xs" aria-label="Chọn số sao">
        <button v-for="star in 5" :key="star" class="material-symbols-outlined text-[30px] transition-colors" :class="star <= form.rating ? 'filled-star text-tertiary' : 'text-on-surface-variant hover:text-tertiary'" type="button" :aria-label="`${star} sao`" @click="form.rating = star">star</button>
      </div>
      <textarea v-model="form.comment" class="mt-md w-full min-w-0 rounded-lg border border-surface-variant bg-background px-md py-3 text-body-sm text-on-surface outline-none placeholder:text-on-surface-variant focus:border-primary" rows="4" maxlength="5000" placeholder="Chia sẻ cảm nhận của bạn (không bắt buộc)"></textarea>
      <p v-if="error" class="mt-sm w-full text-body-sm text-error">{{ error }}</p>
      <p v-if="success" class="mt-sm w-full text-body-sm text-[var(--accent-success)]">{{ success }}</p>
      <button class="mt-md w-full rounded-lg bg-primary px-md py-3 font-display text-button-text font-semibold text-white hover:bg-[var(--accent-primary-hover)] disabled:opacity-50 sm:w-auto" type="submit" :disabled="submitting">{{ submitting ? 'Đang gửi...' : 'Gửi đánh giá' }}</button>
    </form>

    <div v-if="loading" class="grid w-full min-w-0 grid-cols-1 gap-md md:grid-cols-2">
      <div v-for="index in 2" :key="index" class="h-44 w-full animate-pulse rounded-xl border border-surface-variant bg-surface"></div>
    </div>
    <div v-else-if="error && !reviews.length" class="w-full rounded-lg border border-error/40 bg-error/10 p-md text-body-sm text-error">{{ error }}</div>
    <div v-else-if="!reviews.length" class="flex min-h-48 w-full min-w-0 items-center justify-center rounded-xl border border-dashed border-surface-variant bg-surface/40 text-center">
      <div class="w-full min-w-0 max-w-md p-md"><span class="material-symbols-outlined text-5xl text-primary">reviews</span><p class="mt-sm w-full text-body-md text-on-surface">Chưa có đánh giá nào.</p></div>
    </div>
    <div v-else class="grid w-full min-w-0 grid-cols-1 gap-md md:grid-cols-2">
      <article v-for="review in reviews" :key="review.id" class="glass-card w-full min-w-0 rounded-xl p-md">
        <div class="flex w-full min-w-0 items-start gap-sm">
          <img v-if="review.user?.avatar" :src="review.user.avatar" :alt="review.user.name" class="h-10 w-10 shrink-0 rounded-full object-cover" />
          <div v-else class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/15 font-display font-semibold text-primary">{{ review.user?.name?.charAt(0)?.toUpperCase() }}</div>
          <div class="w-full min-w-0"><p class="w-full truncate font-medium text-on-surface">{{ review.user?.name }}</p><p class="w-full font-mono text-[11px] text-on-surface-variant">{{ formatDate(review.created_at) }}</p></div>
        </div>
        <div class="mt-sm flex w-full min-w-0 gap-0.5"><span v-for="star in 5" :key="star" class="material-symbols-outlined text-[18px]" :class="star <= review.rating ? 'filled-star text-tertiary' : 'text-on-surface-variant'">star</span></div>
        <p v-if="review.comment" class="mt-sm w-full min-w-0 whitespace-pre-wrap break-words text-body-sm leading-6 text-on-surface-variant">{{ review.comment }}</p>
      </article>
    </div>

    <button v-if="canLoadMore" class="w-full rounded-lg border border-surface-variant px-md py-3 font-display text-button-text text-on-surface hover:border-primary hover:text-primary disabled:opacity-50" type="button" :disabled="loadingMore" @click="loadMore">{{ loadingMore ? 'Đang tải...' : 'Xem thêm đánh giá' }}</button>
  </section>
</template>
