<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import CourseCard from '../components/CourseCard.vue'
import { getCategories, getCourses } from '../services/api'

const route = useRoute()

const category = ref(null)
const courses = ref([])
const isLoading = ref(true)
const errorMessage = ref('')

const fallbackCategoryName = computed(() => String(route.params.slug ?? '')
  .split('-')
  .filter(Boolean)
  .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
  .join(' '))

const categoryName = computed(() => category.value?.name ?? fallbackCategoryName.value)
const categoryAccent = computed(() => category.value?.accent_color ?? '#FF6B4A')
const categoryStyle = computed(() => ({
  '--category-accent': categoryAccent.value,
}))
const finalPrice = (course) => Number(course.final_price ?? course.discount_price ?? course.price ?? 0)
const highlights = (course) => [
  `Learn ${course.category?.name ?? 'practical skills'} through guided lessons`,
  `${course.level ?? 'All levels'} learning path`,
  `Taught by ${course.instructor?.name ?? 'an EduMarket instructor'}`,
]

const fetchCategoryCourses = async () => {
  isLoading.value = true
  errorMessage.value = ''
  courses.value = []
  category.value = null

  try {
    const categoriesResponse = await getCategories()
    category.value = categoriesResponse.data.data.find(
      (item) => item.slug === route.params.slug && item.is_active,
    ) ?? null

    if (category.value) {
      const coursesResponse = await getCourses({ category_id: category.value.id })
      courses.value = coursesResponse.data.data
    }
  } catch (error) {
    errorMessage.value = error.response?.data?.message ?? 'Unable to load courses.'
  } finally {
    isLoading.value = false
  }
}

watch(() => route.params.slug, fetchCategoryCourses, { immediate: true })
</script>

<template>
  <main class="mx-auto min-h-[calc(100vh-20rem)] w-full max-w-container-max px-margin-mobile py-xl font-body md:px-gutter">
    <header class="mb-xl w-full border-b border-surface-variant pb-lg" :style="categoryStyle">
      <p class="category-accent-text mb-sm w-full font-mono text-label-mono uppercase tracking-widest">Browse category</p>
      <h1 class="w-full font-display text-display-lg-mobile font-bold leading-tight text-on-background md:text-display-lg">
        {{ categoryName }} Courses
      </h1>
    </header>

    <div v-if="isLoading" class="grid w-full grid-cols-1 gap-gutter sm:grid-cols-2 lg:grid-cols-4">
      <div v-for="index in 4" :key="index" class="h-[390px] animate-pulse rounded-lg border border-surface-variant bg-surface"></div>
    </div>

    <p v-else-if="errorMessage" class="w-full py-xl text-center text-error">{{ errorMessage }}</p>

    <div v-else-if="courses.length" class="grid w-full min-w-0 grid-cols-1 gap-gutter sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      <div v-for="course in courses" :key="course.id" class="flex w-full min-w-0 justify-center xl:justify-start">
        <CourseCard
          :course-id="course.id"
          :thumbnail="course.thumbnail_url ?? ''"
          :level="course.level ?? 'All Levels'"
          :tech-tag="course.category?.name ?? 'Course'"
          :category-accent="course.category?.accent_color ?? category?.accent_color ?? '#FF6B4A'"
          :title="course.title"
          :instructor="course.instructor?.name ?? 'EduMarket Instructor'"
          :rating="Number(course.rating_avg ?? 0)"
          :price="finalPrice(course)"
          :description="course.description ?? 'Explore this course on EduMarket.'"
          :highlights="highlights(course)"
          :updated-at="course.published_at ? new Date(course.published_at).toLocaleDateString() : 'recently'"
        />
      </div>
    </div>

    <div v-else class="flex min-h-72 w-full items-center justify-center rounded-lg border border-surface-variant bg-surface/50 px-md text-center">
      <p class="w-full text-body-md text-on-surface-variant">No courses found in this category yet.</p>
    </div>
  </main>
</template>

<style scoped>
.category-accent-text {
  color: var(--category-accent);
}
</style>
