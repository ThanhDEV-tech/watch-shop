<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import CourseCard from './CourseCard.vue'
import { getCourses } from '../services/api'

const props = defineProps({
  categoryId: { type: [Number, String], default: null },
})

const courses = ref([])
const isLoading = ref(true)
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

  return {
    maskImage,
    WebkitMaskImage: maskImage,
  }
})

const finalPrice = (course) => Number(course.final_price ?? course.discount_price ?? course.price ?? 0)

const courseHighlights = (course) => [
  `Learn ${course.category?.name ?? 'practical skills'} through a focused course`,
  `${course.level ?? 'All levels'} learning path`,
  `Guided by ${course.instructor?.name ?? 'an EduMarket instructor'}`,
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
    left: direction * Math.min(680, scroller.clientWidth * 0.85),
    behavior: 'smooth',
  })
}

const fetchCourses = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const params = props.categoryId ? { category_id: props.categoryId } : {}
    const response = await getCourses(params)
    courses.value = response.data.data
  } catch (error) {
    courses.value = []
    errorMessage.value = error.response?.data?.message ?? 'Unable to load courses.'
  } finally {
    isLoading.value = false
    await nextTick()
    updateScrollState()
  }
}

watch(() => props.categoryId, fetchCourses, { immediate: true })

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
  <section id="courses" class="w-full bg-surface-container-low font-body">
    <div class="mx-auto w-full min-w-0 max-w-container-max px-margin-mobile py-xl md:px-gutter">
      <div class="mb-lg flex w-full min-w-0 items-end justify-between">
        <div class="w-full min-w-0">
          <h2 class="mb-xs w-full font-display text-headline-md font-semibold text-on-background">Recommended for you</h2>
          <p class="w-full text-body-md text-on-surface-variant">Explore approved courses from EduMarket instructors.</p>
        </div>
      </div>

      <div v-if="isLoading" class="grid w-full grid-cols-1 gap-gutter sm:grid-cols-2 lg:grid-cols-4" aria-label="Loading courses">
        <div v-for="index in 4" :key="index" class="h-[390px] animate-pulse rounded-lg border border-surface-variant bg-surface"></div>
      </div>

      <p v-else-if="errorMessage" class="w-full py-xl text-center text-error">{{ errorMessage }}</p>

      <div v-else-if="courses.length" class="relative w-full min-w-0 py-4">
        <button
          v-show="canScrollLeft"
          class="absolute left-0 top-1/2 z-10 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-surface-variant bg-surface text-on-surface shadow-[0_12px_24px_rgba(0,0,0,0.35)] transition-colors hover:border-primary hover:text-primary md:flex"
          type="button"
          aria-label="Cuộn khóa học sang trái"
          @click="scrollCourses(-1)"
        >
          <span class="material-symbols-outlined text-[22px]">chevron_left</span>
        </button>

        <div class="w-full min-w-0 overflow-hidden" :style="maskStyle">
          <div
            ref="carousel"
            class="no-scrollbar w-full min-w-0 overflow-x-auto scroll-smooth px-1 py-4 lg:pb-40"
            @scroll="updateScrollState"
          >
            <div class="flex w-max min-w-0 gap-gutter">
              <div v-for="course in courses" :key="course.id" class="shrink-0">
                <CourseCard
                  :course-id="course.id"
                  :thumbnail="course.thumbnail_url ?? ''"
                  :level="course.level ?? 'All Levels'"
                  :tech-tag="course.category?.name ?? 'Course'"
                  :category-accent="course.category?.accent_color ?? '#FF6B4A'"
                  :title="course.title"
                  :instructor="course.instructor?.name ?? 'EduMarket Instructor'"
                  :rating="Number(course.rating_avg ?? 0)"
                  :price="finalPrice(course)"
                  :description="course.description ?? 'Explore this course on EduMarket.'"
                  :highlights="courseHighlights(course)"
                  :updated-at="course.published_at ? new Date(course.published_at).toLocaleDateString() : 'recently'"
                />
              </div>
            </div>
          </div>
        </div>

        <button
          v-show="canScrollRight"
          class="absolute right-0 top-1/2 z-10 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-surface-variant bg-surface text-on-surface shadow-[0_12px_24px_rgba(0,0,0,0.35)] transition-colors hover:border-primary hover:text-primary md:flex"
          type="button"
          aria-label="Cuộn khóa học sang phải"
          @click="scrollCourses(1)"
        >
          <span class="material-symbols-outlined text-[22px]">chevron_right</span>
        </button>
      </div>

      <p v-else class="w-full py-xl text-center text-on-surface-variant">No courses found in this category.</p>
    </div>
  </section>
</template>
