<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { getCourseChapters } from '../services/api'
import { useCartStore } from '../stores/cart'
import { formatCurrency } from '../utils/formatCurrency'

const cartStore = useCartStore()
const router = useRouter()
const cartFeedback = ref('')
const cartFeedbackType = ref('success')
const isOpeningLesson = ref(false)

const props = defineProps({
  course: { type: Object, required: true },
})

const emit = defineEmits(['preview-course'])

const discountPercent = computed(() => {
  if (!props.course.originalPrice) return 0

  return Math.round((1 - Number(props.course.price) / Number(props.course.originalPrice)) * 100)
})

const addCourseToCart = async () => {
  cartFeedback.value = ''

  try {
    const response = await cartStore.addItem(props.course.id)
    cartFeedbackType.value = 'success'
    cartFeedback.value = response.message
  } catch {
    cartFeedbackType.value = 'error'
    cartFeedback.value = cartStore.error
  }
}

const continueLearning = async () => {
  if (isOpeningLesson.value) return

  isOpeningLesson.value = true
  cartFeedback.value = ''

  try {
    const response = await getCourseChapters(props.course.id)
    const lessons = (response.data.data ?? []).flatMap((chapter) => chapter.lessons ?? [])
    const targetLesson = lessons.find((lesson) => !lesson.is_completed) ?? lessons[0]

    if (!targetLesson) {
      cartFeedbackType.value = 'error'
      cartFeedback.value = 'Khóa học này chưa có bài học.'
      return
    }

    await router.push({
      name: 'lesson-player',
      params: {
        courseId: props.course.id,
        lessonId: targetLesson.id,
      },
    })
  } catch (error) {
    cartFeedbackType.value = 'error'
    cartFeedback.value = error.response?.data?.message ?? 'Không thể mở bài học lúc này.'
  } finally {
    isOpeningLesson.value = false
  }
}

const includedItems = [
  ['videocam', '24 hours on-demand video'],
  ['description', '12 coding exercises'],
  ['article', 'Full lifetime access'],
  ['smartphone', 'Access on mobile and TV'],
  ['workspace_premium', 'Certificate of completion'],
]
</script>

<template>
  <aside class="w-full min-w-0 space-y-gutter">
    <div class="glass-card w-full rounded-xl overflow-hidden shadow-2xl">
      <div class="relative h-56 bg-surface-container-low group cursor-pointer overflow-hidden">
        <img v-if="course.thumbnail" class="w-full h-full object-cover opacity-80 group-hover:scale-105 transition-transform duration-700" :src="course.thumbnail" :alt="course.title" />
        <div v-else class="flex h-full w-full items-center justify-center text-primary">
          <span class="material-symbols-outlined text-6xl">school</span>
        </div>
        <button
          class="absolute inset-0 flex w-full min-w-0 cursor-pointer items-center justify-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed"
          type="button"
          :disabled="!course.hasFreePreview"
          @click="emit('preview-course')"
        >
          <div class="w-16 min-w-0 h-16 bg-white/10 backdrop-blur-md rounded-full flex items-center justify-center border border-white/20 group-hover:scale-110 transition-transform">
            <span class="material-symbols-outlined text-white text-4xl">play_arrow</span>
          </div>
        </button>
        <div class="absolute bottom-4 left-0 right-0 text-center">
          <span class="font-mono text-label-mono text-white/80 bg-black/40 backdrop-blur px-3 py-1 rounded-full">Preview this course</span>
        </div>
      </div>
      <div class="w-full p-md space-y-md">
        <div class="flex w-full flex-wrap items-baseline gap-base">
          <span class="font-display text-headline-md text-primary">{{ formatCurrency(course.price) }}</span>
          <span v-if="course.originalPrice" class="text-body-md text-on-surface-variant line-through">{{ formatCurrency(course.originalPrice) }}</span>
          <span v-if="discountPercent" class="ml-auto font-mono text-label-mono text-tertiary">{{ discountPercent }}% OFF</span>
        </div>
        <div class="space-y-sm">
          <button
            v-if="course.isEnrolled"
            class="w-full cursor-pointer rounded-lg bg-primary-container py-4 font-display text-button-text text-on-primary-container shadow-lg shadow-primary-container/20 transition-all hover:-translate-y-0.5 hover:opacity-90 active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-60"
            type="button"
            :disabled="isOpeningLesson"
            @click="continueLearning"
          >
            {{ isOpeningLesson ? 'Opening lesson...' : 'Continue Learning' }}
          </button>
          <template v-else>
            <button class="w-full cursor-pointer py-4 bg-primary-container text-on-primary-container rounded-lg font-display text-button-text active:scale-95 transition-all shadow-lg shadow-primary-container/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-60" type="button" :disabled="cartStore.loading" @click="addCourseToCart">{{ cartStore.loading ? 'Adding...' : 'Enroll Now' }}</button>
            <button class="w-full cursor-pointer py-3 border border-surface-variant text-on-surface rounded-lg font-display text-button-text hover:bg-surface-container transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-60" type="button" :disabled="cartStore.loading" @click="addCourseToCart">Add to Cart</button>
          </template>
          <p
            v-if="cartFeedback"
            class="w-full text-body-sm"
            :class="cartFeedbackType === 'error' ? 'text-error' : 'text-[var(--accent-success)]'"
          >
            {{ cartFeedback }}
          </p>
        </div>
        <p class="w-full text-center text-body-sm text-on-surface-variant opacity-60">30-Day Money-Back Guarantee</p>
        <div class="h-px bg-surface-variant"></div>
        <div class="w-full space-y-sm">
          <h4 class="font-display text-[16px] text-on-surface">This course includes:</h4>
          <ul class="space-y-sm">
            <li v-for="[icon, label] in includedItems" :key="label" class="flex w-full min-w-0 items-center gap-sm text-body-sm text-on-surface-variant">
              <span class="material-symbols-outlined shrink-0 text-primary text-[20px]">{{ icon }}</span>
              <span class="min-w-0">{{ label }}</span>
            </li>
          </ul>
        </div>
      </div>
    </div>
    <div class="w-full bg-surface-container-low border border-dashed border-surface-variant p-md rounded-xl space-y-sm">
      <h5 class="font-display text-[14px] text-primary">Team Training?</h5>
      <p class="w-full text-body-sm text-on-surface-variant">Get this course and 10,000+ top-rated courses for your team.</p>
      <button class="cursor-pointer rounded font-mono text-label-mono text-on-surface underline hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background" type="button">EduMarket for Business</button>
    </div>
  </aside>
</template>
