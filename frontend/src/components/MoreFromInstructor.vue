<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import CourseCard from './CourseCard.vue'
import { getPublicInstructorCourses } from '../services/api'

const props = defineProps({
  instructorId: { type: [Number, String], default: null },
  excludeCourseId: { type: [Number, String], default: null },
  instructorName: { type: String, default: 'giảng viên này' },
})

const courses = ref([])
const isLoading = ref(false)
const errorMessage = ref('')
const carousel = ref(null)
const canScrollLeft = ref(false)
const canScrollRight = ref(false)

const maskStyle = computed(() => {
  let maskImage = 'none'

  if (canScrollLeft.value && canScrollRight.value) {
    maskImage = 'linear-gradient(to right, transparent 0, black 8%, black 92%, transparent 100%)'
  } else if (!canScrollLeft.value && canScrollRight.value) {
    maskImage = 'linear-gradient(to right, black 0, black 92%, transparent 100%)'
  } else if (canScrollLeft.value && !canScrollRight.value) {
    maskImage = 'linear-gradient(to right, transparent 0, black 8%, black 100%)'
  }

  return { maskImage, WebkitMaskImage: maskImage }
})

const finalPrice = (course) => Number(course.final_price ?? course.discount_price ?? course.price ?? 0)

const courseHighlights = (course) => [
  `Cùng phong cách giảng dạy của ${props.instructorName}`,
  `${course.level ?? 'All levels'} learning path`,
  `Danh mục ${course.category?.name ?? 'EduMarket'}`,
]

const updateScrollState = () => {
  const scroller = carousel.value
  if (!scroller) return

  const maxScrollLeft = scroller.scrollWidth - scroller.clientWidth
  canScrollLeft.value = scroller.scrollLeft > 4
  canScrollRight.value = scroller.scrollLeft < maxScrollLeft - 4
}

const scrollCourses = (direction) => {
  const scroller = carousel.value
  if (!scroller) return

  scroller.scrollBy({
    left: direction * Math.min(640, scroller.clientWidth * 0.85),
    behavior: 'smooth',
  })
}

const loadCourses = async () => {
  if (!props.instructorId) {
    courses.value = []
    return
  }

  isLoading.value = true
  errorMessage.value = ''

  try {
    const response = await getPublicInstructorCourses(props.instructorId, {
      exclude_course_id: props.excludeCourseId,
      per_page: 6,
    })
    courses.value = response.data.data?.items ?? []
  } catch (error) {
    courses.value = []
    errorMessage.value = error.response?.data?.message ?? 'Không thể tải khóa học khác của giảng viên.'
  } finally {
    isLoading.value = false
    await nextTick()
    updateScrollState()
  }
}

watch(() => [props.instructorId, props.excludeCourseId], loadCourses, { immediate: true })
watch(courses, async () => {
  await nextTick()
  updateScrollState()
})

onMounted(() => {
  window.addEventListener('resize', updateScrollState)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', updateScrollState)
})
</script>

<template>
  <section v-if="isLoading || errorMessage || courses.length" class="glass-card w-full min-w-0 rounded-xl p-md">
    <div class="mb-md w-full min-w-0">
      <p class="w-full font-mono text-label-mono uppercase tracking-widest text-primary">More from instructor</p>
      <h2 class="mt-xs w-full font-display text-headline-sm font-semibold text-on-surface">Các khóa học khác của {{ instructorName }}</h2>
    </div>

    <div v-if="isLoading" class="grid w-full grid-cols-1 gap-md sm:grid-cols-2">
      <div v-for="index in 2" :key="index" class="h-[360px] animate-pulse rounded-lg border border-surface-variant bg-surface-container-low"></div>
    </div>

    <p v-else-if="errorMessage" class="w-full rounded-lg border border-error/40 bg-error/10 p-sm text-body-sm text-error">{{ errorMessage }}</p>

    <div v-else class="relative w-full min-w-0 py-2">
      <button
        v-show="canScrollLeft"
        class="absolute left-0 top-1/2 z-10 hidden h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-surface-variant bg-surface text-on-surface shadow-[0_12px_24px_rgba(0,0,0,0.35)] transition-colors hover:border-primary hover:text-primary md:flex"
        type="button"
        aria-label="Cuộn khóa học của giảng viên sang trái"
        @click="scrollCourses(-1)"
      >
        <span class="material-symbols-outlined text-[22px]">chevron_left</span>
      </button>

      <div class="w-full min-w-0 overflow-hidden" :style="maskStyle">
        <div ref="carousel" class="no-scrollbar w-full min-w-0 overflow-x-auto scroll-smooth px-1 py-4 lg:pb-40" @scroll="updateScrollState">
          <div class="flex w-max min-w-0 gap-md">
            <div v-for="course in courses" :key="course.id" class="shrink-0">
              <CourseCard
                :course-id="course.id"
                :thumbnail="course.thumbnail_url ?? ''"
                :level="course.level ?? 'All Levels'"
                :tech-tag="course.category?.name ?? 'Course'"
                :category-accent="course.category?.accent_color ?? '#FF6B4A'"
                :title="course.title"
                :instructor="course.instructor?.name ?? instructorName"
                :rating="Number(course.rating_avg ?? 0)"
                :price="finalPrice(course)"
                :description="course.description ?? 'Một khóa học khác từ cùng giảng viên.'"
                :highlights="courseHighlights(course)"
                :updated-at="course.published_at ? new Date(course.published_at).toLocaleDateString('vi-VN') : 'gần đây'"
              />
            </div>
          </div>
        </div>
      </div>

      <button
        v-show="canScrollRight"
        class="absolute right-0 top-1/2 z-10 hidden h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-surface-variant bg-surface text-on-surface shadow-[0_12px_24px_rgba(0,0,0,0.35)] transition-colors hover:border-primary hover:text-primary md:flex"
        type="button"
        aria-label="Cuộn khóa học của giảng viên sang phải"
        @click="scrollCourses(1)"
      >
        <span class="material-symbols-outlined text-[22px]">chevron_right</span>
      </button>
    </div>
  </section>
</template>
