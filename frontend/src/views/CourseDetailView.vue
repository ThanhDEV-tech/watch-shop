<script setup>
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import BaseModal from '../components/ui/BaseModal.vue'
import CourseCurriculum from '../components/CourseCurriculum.vue'
import CourseDetailHero from '../components/CourseDetailHero.vue'
import CourseInstructor from '../components/CourseInstructor.vue'
import CourseLearningOutcomes from '../components/CourseLearningOutcomes.vue'
import CoursePurchaseCard from '../components/CoursePurchaseCard.vue'
import CourseRequirements from '../components/CourseRequirements.vue'
import CourseReviews from '../components/CourseReviews.vue'
import MoreFromInstructor from '../components/MoreFromInstructor.vue'
import RelatedCourses from '../components/RelatedCourses.vue'
import { getCourseById, getCourseChapters } from '../services/api'

const route = useRoute()

const course = ref(null)
const isLoading = ref(true)
const isNotFound = ref(false)
const errorMessage = ref('')
const activePreviewLesson = ref(null)
const isPreviewModalOpen = ref(false)

const formatDuration = (seconds) => {
  const totalSeconds = Number(seconds ?? 0)
  const minutes = Math.floor(totalSeconds / 60)
  const remainingSeconds = totalSeconds % 60

  return `${minutes}:${String(remainingSeconds).padStart(2, '0')}`
}

const normalizeCourse = (data, curriculum = []) => {
  const finalPrice = Number(data.final_price ?? data.discount_price ?? data.price ?? 0)
  const originalPrice = data.discount_price ? Number(data.price ?? 0) : null
  const chapters = (curriculum ?? []).map((chapter) => ({
    title: chapter.title,
    summary: `${chapter.lessons?.length ?? 0} lessons`,
    lessons: (chapter.lessons ?? []).map((lesson) => ({
      id: lesson.id,
      title: lesson.title,
      duration: formatDuration(lesson.duration_seconds),
      locked: !lesson.is_free_preview && !data.is_enrolled,
      canWatch: Boolean(lesson.is_free_preview || data.is_enrolled),
      isFreePreview: Boolean(lesson.is_free_preview),
      videoUrl: lesson.youtube_url ?? '',
      content: lesson.content ?? '',
    })),
  }))
  const firstFreePreviewLesson = chapters.flatMap((chapter) => chapter.lessons).find((lesson) => lesson.isFreePreview) ?? null

  return {
    id: data.id,
    level: data.level ?? 'All Levels',
    title: data.title,
    description: data.description ?? 'Course details are being updated.',
    rating: Number(data.rating_avg ?? 0).toFixed(1),
    students: Number(data.total_students ?? 0).toLocaleString(),
    price: finalPrice,
    originalPrice,
    thumbnail: data.thumbnail_url ?? '',
    isEnrolled: Boolean(data.is_enrolled),
    outcomes: data.content ? [data.content] : [],
    requirements: Array.isArray(data.requirements) ? data.requirements : [],
    chapters,
    firstFreePreviewLesson,
    hasFreePreview: Boolean(firstFreePreviewLesson),
    instructor: {
      id: data.instructor?.id ?? null,
      name: data.instructor?.name ?? 'EduMarket Instructor',
      role: data.instructor?.role?.display_name ?? 'Instructor',
      avatar: data.instructor?.avatar_url ?? data.instructor?.avatar ?? '',
      bio: data.instructor?.bio ?? '',
      totalCourses: data.instructor?.instructor_stats?.total_courses ?? 0,
      totalStudents: data.instructor?.instructor_stats?.total_students ?? 0,
      ratingAvg: data.instructor?.instructor_stats?.rating_avg ?? 0,
      skills: data.category?.name ? [data.category.name] : [],
    },
  }
}

const refreshCourseRating = async () => {
  try {
    const response = await getCourseById(route.params.id)
    if (course.value) course.value.rating = Number(response.data.data.rating_avg ?? 0).toFixed(1)
  } catch {
    // The review list is already refreshed; keep the previous summary if this lightweight refresh fails.
  }
}

const openLessonPreview = (lesson) => {
  if (!lesson?.canWatch) return

  activePreviewLesson.value = lesson
  isPreviewModalOpen.value = true
}

const closeLessonPreview = () => {
  isPreviewModalOpen.value = false
  activePreviewLesson.value = null
}

const toEmbedUrl = (url) => {
  if (!url) return ''

  const youtubeMatch = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([\w-]+)/)
  if (youtubeMatch?.[1]) {
    return `https://www.youtube.com/embed/${youtubeMatch[1]}`
  }

  return url
}

const fetchCourse = async () => {
  isLoading.value = true
  isNotFound.value = false
  errorMessage.value = ''
  course.value = null

  try {
    const [courseResponse, chaptersResponse] = await Promise.all([
      getCourseById(route.params.id),
      getCourseChapters(route.params.id),
    ])
    course.value = normalizeCourse(courseResponse.data.data, chaptersResponse.data.data)
  } catch (error) {
    if (error.response?.status === 404) {
      isNotFound.value = true
    } else {
      errorMessage.value = error.response?.data?.message ?? 'Unable to load this course.'
    }
  } finally {
    isLoading.value = false
  }
}

watch(() => route.params.id, fetchCourse, { immediate: true })
</script>

<template>
  <main class="w-full max-w-container-max mx-auto px-margin-mobile md:px-gutter py-lg">
    <p v-if="route.query.notice" class="mb-md w-full rounded-lg border border-tertiary/40 bg-tertiary/10 p-md text-body-sm text-tertiary">
      {{ route.query.notice }}
    </p>
    <div v-if="isLoading" class="grid w-full grid-cols-1 gap-lg lg:grid-cols-12" aria-label="Loading course">
      <div class="h-[36rem] animate-pulse rounded-xl bg-surface lg:col-span-8"></div>
      <div class="h-[32rem] animate-pulse rounded-xl bg-surface lg:col-span-4"></div>
    </div>

    <div v-else-if="isNotFound" class="flex min-h-[400px] w-full min-w-0 items-center justify-center text-center md:min-h-[calc(100vh-10rem)]">
      <div class="w-full min-w-0 max-w-[32rem] rounded-xl border border-surface-variant bg-surface p-lg">
        <span class="material-symbols-outlined text-5xl text-primary">search_off</span>
        <h1 class="mt-md w-full font-display text-headline-md text-on-surface">Course not found</h1>
        <RouterLink to="/" class="mt-md inline-block text-primary hover:underline">Return to courses</RouterLink>
      </div>
    </div>

    <p v-else-if="errorMessage" class="w-full py-xl text-center text-error">{{ errorMessage }}</p>

    <div v-else-if="course" class="course-detail-layout grid w-full min-w-0 grid-cols-1 lg:grid-cols-12 gap-lg items-start">
      <div class="course-detail-content w-full min-w-0 lg:col-span-8 space-y-lg">
        <CourseDetailHero :course="course" />
        <div class="h-px w-full bg-surface-variant opacity-30 flex justify-center items-center">
          <span class="px-4 bg-background text-on-surface-variant font-mono text-label-mono">&lt; /&gt;</span>
        </div>
        <CourseLearningOutcomes v-if="course.outcomes.length" :outcomes="course.outcomes" />
        <CourseRequirements v-if="course.requirements.length" :requirements="course.requirements" />
        <CourseCurriculum v-if="course.chapters.length" :chapters="course.chapters" @lesson-click="openLessonPreview" />
        <CourseInstructor :instructor="course.instructor" />
        <MoreFromInstructor :instructor-id="course.instructor.id" :exclude-course-id="course.id" :instructor-name="course.instructor.name" />
        <CourseReviews :course-id="course.id" :rating-average="course.rating" :is-enrolled="course.isEnrolled" @review-submitted="refreshCourseRating" />
        <RelatedCourses :course-id="course.id" />
      </div>
      <div class="course-detail-sidebar w-full min-w-0 lg:col-span-4 lg:sticky lg:top-24">
        <CoursePurchaseCard :course="course" @preview-course="openLessonPreview(course.firstFreePreviewLesson)" />
      </div>
    </div>

    <BaseModal v-if="isPreviewModalOpen && activePreviewLesson" max-width="xl" aria-labelledby="lesson-preview-title" @close="closeLessonPreview">
      <div class="flex w-full min-w-0 flex-col overflow-hidden rounded-xl border border-surface-variant bg-background shadow-2xl">
        <header class="flex w-full min-w-0 items-center justify-between gap-md border-b border-surface-variant bg-surface p-md">
          <div class="w-full min-w-0">
            <p class="w-full font-mono text-[11px] uppercase tracking-widest text-primary">Free preview</p>
            <h2 id="lesson-preview-title" class="w-full font-display text-headline-sm font-semibold text-on-surface">{{ activePreviewLesson.title }}</h2>
          </div>
          <button class="material-symbols-outlined h-10 w-10 shrink-0 rounded-lg text-on-surface-variant hover:bg-surface-container-highest" type="button" aria-label="Đóng" @click="closeLessonPreview">close</button>
        </header>
        <div class="w-full min-w-0 space-y-md p-md">
          <div v-if="activePreviewLesson.videoUrl" class="aspect-video w-full overflow-hidden rounded-xl bg-black">
            <iframe
              class="h-full w-full"
              :src="toEmbedUrl(activePreviewLesson.videoUrl)"
              title="Lesson preview"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
              allowfullscreen
            ></iframe>
          </div>
          <p v-else class="w-full rounded-lg border border-surface-variant bg-surface-container-low p-sm text-body-sm text-on-surface-variant">Video preview is not available for this lesson yet.</p>
        </div>
      </div>
    </BaseModal>
  </main>
</template>

<style scoped>
@media (min-width: 1024px) {
  .course-detail-layout {
    grid-template-columns: repeat(12, minmax(0, 1fr));
  }

  .course-detail-content {
    grid-column: span 8 / span 8;
  }

  .course-detail-sidebar {
    position: sticky;
    top: 6rem;
    grid-column: span 4 / span 4;
    align-self: start;
  }
}
</style>
