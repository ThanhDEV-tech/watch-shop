<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import CourseCard from '../components/CourseCard.vue'
import { getCourses } from '../services/api'

const route = useRoute()

const courses = ref([])
const isLoading = ref(false)
const errorMessage = ref('')

const keyword = computed(() => String(route.query.q ?? '').trim())
const resultCount = computed(() => courses.value.length)

const finalPrice = (course) => Number(course.final_price ?? course.discount_price ?? course.price ?? 0)
const highlights = (course) => [
  `Thực hành ${course.category?.name ?? 'kỹ năng chuyên môn'} qua bài học có hướng dẫn`,
  `Lộ trình ${course.level ?? 'phù hợp nhiều trình độ'}`,
  `Đồng hành cùng ${course.instructor?.name ?? 'giảng viên EduMarket'}`,
]

const fetchSearchResults = async () => {
  courses.value = []
  errorMessage.value = ''

  if (!keyword.value) return

  isLoading.value = true

  try {
    const response = await getCourses({ search: keyword.value })
    courses.value = response.data.data ?? []
  } catch (error) {
    errorMessage.value = error.response?.data?.message ?? 'Không thể tải kết quả tìm kiếm.'
  } finally {
    isLoading.value = false
  }
}

watch(keyword, fetchSearchResults, { immediate: true })
</script>

<template>
  <main class="mx-auto min-h-[calc(100vh-20rem)] w-full min-w-0 max-w-container-max px-margin-mobile py-xl font-body md:px-gutter">
    <header class="w-full min-w-0 border-b border-surface-variant pb-lg">
      <p class="w-full font-mono text-label-mono uppercase tracking-widest text-primary">Search results</p>
      <h1 class="mt-sm w-full font-display text-display-lg-mobile font-bold leading-tight text-on-background md:text-display-lg">
        Tìm kiếm khóa học
      </h1>
      <p v-if="keyword" class="mt-sm w-full text-body-lg text-on-surface-variant">
        Tìm thấy <span class="font-mono text-primary">{{ resultCount }}</span> kết quả cho
        <span class="font-semibold text-on-surface">“{{ keyword }}”</span>
      </p>
      <p v-else class="mt-sm w-full text-body-lg text-on-surface-variant">
        Nhập từ khóa trên thanh tìm kiếm để khám phá khóa học phù hợp.
      </p>
    </header>

    <section class="mt-xl w-full min-w-0">
      <div v-if="isLoading" class="grid w-full min-w-0 grid-cols-1 gap-gutter sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        <div v-for="index in 8" :key="index" class="h-[470px] w-full animate-pulse rounded-lg border border-surface-variant bg-surface"></div>
      </div>

      <div v-else-if="errorMessage" class="flex min-h-72 w-full min-w-0 items-center justify-center rounded-lg border border-error/40 bg-error/10 p-md text-center">
        <div class="w-full min-w-0 max-w-md">
          <span class="material-symbols-outlined text-5xl text-error">error</span>
          <p class="mt-sm w-full text-body-md text-error">{{ errorMessage }}</p>
          <button class="mt-md w-full rounded-lg border border-surface-variant px-md py-3 text-body-sm text-on-surface hover:border-primary hover:text-primary" type="button" @click="fetchSearchResults">
            Thử lại
          </button>
        </div>
      </div>

      <div v-else-if="courses.length" class="grid w-full min-w-0 grid-cols-1 gap-gutter sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        <div v-for="course in courses" :key="course.id" class="flex w-full min-w-0 justify-center xl:justify-start">
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
            :description="course.description ?? 'Khám phá khóa học này trên EduMarket.'"
            :highlights="highlights(course)"
            :updated-at="course.published_at ? new Date(course.published_at).toLocaleDateString('vi-VN') : 'gần đây'"
          />
        </div>
      </div>

      <div v-else class="flex min-h-72 w-full min-w-0 items-center justify-center rounded-lg border border-dashed border-surface-variant bg-surface/50 p-md text-center">
        <div class="w-full min-w-0 max-w-md">
          <span class="material-symbols-outlined text-6xl text-primary">search_off</span>
          <h2 class="mt-sm w-full font-display text-headline-sm font-semibold text-on-surface">
            Không tìm thấy khóa học phù hợp
          </h2>
          <p class="mt-xs w-full text-body-sm text-on-surface-variant">
            Thử dùng từ khóa ngắn hơn, tên công nghệ khác, hoặc quay lại trang chủ để xem gợi ý mới.
          </p>
          <RouterLink to="/" class="mt-md inline-flex w-full items-center justify-center rounded-lg bg-primary px-md py-3 font-display text-body-sm font-semibold text-on-primary hover:opacity-90">
            Quay lại trang chủ
          </RouterLink>
        </div>
      </div>
    </section>
  </main>
</template>
