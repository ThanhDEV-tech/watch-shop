<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { getCourseChapters, getMyCourses } from '../services/api'

const router = useRouter()
const enrollments = ref([])
const isLoading = ref(true)
const errorMessage = ref('')
const openingCourseId = ref(null)

const fetchMyCourses = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const response = await getMyCourses()
    enrollments.value = response.data.data ?? []
  } catch (error) {
    errorMessage.value = error.response?.data?.message ?? 'Không thể tải danh sách khóa học của bạn.'
  } finally {
    isLoading.value = false
  }
}

const continueLearning = async (enrollment) => {
  const courseId = enrollment.course.id
  openingCourseId.value = courseId
  errorMessage.value = ''

  try {
    const response = await getCourseChapters(courseId)
    const lessons = (response.data.data ?? []).flatMap((chapter) => chapter.lessons ?? [])
    const targetLesson = lessons.find((lesson) => !lesson.is_completed) ?? lessons[0]

    if (!targetLesson) {
      await router.push({
        name: 'course-detail',
        params: { id: courseId },
        query: { notice: 'Khóa học này chưa có bài học.' },
      })
      return
    }

    await router.push({
      name: 'lesson-player',
      params: { courseId, lessonId: targetLesson.id },
    })
  } catch (error) {
    errorMessage.value = error.response?.data?.message ?? 'Không thể mở khóa học này.'
  } finally {
    openingCourseId.value = null
  }
}

onMounted(fetchMyCourses)
</script>

<template>
  <main class="mx-auto min-h-[calc(100vh-5rem)] w-full max-w-container-max px-margin-mobile py-lg md:px-gutter">
    <div class="mb-lg w-full min-w-0">
      <p class="mb-sm w-full font-mono text-label-mono uppercase tracking-widest text-primary">Your learning path</p>
      <h1 class="w-full font-display text-headline-md text-on-surface">My Courses</h1>
      <p class="mt-sm w-full max-w-2xl text-body-md text-on-surface-variant">Continue where you left off and keep your learning momentum.</p>
    </div>

    <p v-if="errorMessage" class="mb-md w-full rounded-lg border border-error/40 bg-error/10 p-md text-body-sm text-error">
      {{ errorMessage }}
    </p>

    <div v-if="isLoading" class="grid w-full grid-cols-1 gap-md md:grid-cols-2 xl:grid-cols-3" aria-label="Loading enrolled courses">
      <div v-for="index in 6" :key="index" class="h-[24rem] animate-pulse rounded-lg border border-surface-variant bg-surface"></div>
    </div>

    <div v-else-if="enrollments.length" class="grid w-full grid-cols-1 gap-md md:grid-cols-2 xl:grid-cols-3">
      <article
        v-for="enrollment in enrollments"
        :key="enrollment.id"
        class="course-card-element flex w-full min-w-0 flex-col overflow-hidden rounded-lg border border-surface-variant bg-surface"
      >
        <div class="aspect-video w-full overflow-hidden bg-surface-container-lowest">
          <img
            v-if="enrollment.course.thumbnail_url"
            class="h-full w-full object-cover"
            :src="enrollment.course.thumbnail_url"
            :alt="enrollment.course.title"
          />
          <div v-else class="flex h-full w-full items-center justify-center text-primary">
            <span class="material-symbols-outlined text-6xl">school</span>
          </div>
        </div>

        <div class="flex w-full min-w-0 flex-1 flex-col p-md">
          <div class="mb-sm flex w-full min-w-0 items-center justify-between gap-sm">
            <span class="rounded border border-outline-variant bg-surface-variant px-2 py-1 font-mono text-[10px] uppercase text-on-surface-variant">
              {{ enrollment.course.level }}
            </span>
            <span class="shrink-0 font-mono text-label-mono text-primary">{{ enrollment.progress_percent }}%</span>
          </div>

          <h2 class="w-full min-w-0 font-display text-[18px] font-semibold leading-7 text-on-surface">{{ enrollment.course.title }}</h2>
          <p class="mt-xs w-full min-w-0 text-body-sm text-on-surface-variant">by {{ enrollment.course.instructor?.name ?? 'EduMarket Instructor' }}</p>

          <div class="mt-md w-full min-w-0">
            <div class="h-2 w-full overflow-hidden rounded-full bg-surface-container-highest" role="progressbar" aria-label="Tiến độ khóa học" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="enrollment.progress_percent">
              <div
                class="h-full rounded-full bg-primary transition-[width] duration-300 ease-out"
                :style="{ width: `${Math.min(100, Math.max(0, Number(enrollment.progress_percent)))}%` }"
              ></div>
            </div>
            <div class="mt-xs flex w-full min-w-0 flex-wrap items-center justify-between gap-xs">
              <p class="min-w-0 text-body-sm text-on-surface-variant">{{ Number(enrollment.progress_percent) >= 100 ? 'Hoàn thành toàn bộ bài học' : 'Đang học' }}</p>
              <span v-if="Number(enrollment.progress_percent) >= 100" class="shrink-0 rounded border border-[var(--accent-success)]/40 bg-[var(--accent-success)]/10 px-2 py-1 font-mono text-[10px] font-medium uppercase text-[var(--accent-success)]">Đã hoàn thành</span>
            </div>
          </div>

          <button
            class="mt-md w-full rounded-lg bg-primary px-md py-3 font-display text-button-text font-semibold text-on-primary transition-all hover:-translate-y-0.5 hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
            type="button"
            :disabled="openingCourseId === enrollment.course.id"
            @click="continueLearning(enrollment)"
          >
            {{ openingCourseId === enrollment.course.id ? 'Opening...' : (enrollment.completed_at ? 'Review Course' : 'Continue Learning') }}
          </button>
        </div>
      </article>
    </div>

    <div v-else class="flex min-h-[360px] w-full min-w-0 items-center justify-center rounded-lg border border-surface-variant bg-surface/60 text-center">
      <div class="w-full min-w-0 max-w-md p-lg">
        <span class="material-symbols-outlined text-6xl text-primary">menu_book</span>
        <h2 class="mt-md w-full font-display text-headline-sm text-on-surface">No enrolled courses yet</h2>
        <p class="mt-sm w-full text-body-md text-on-surface-variant">Choose a course and complete checkout to start learning.</p>
        <RouterLink to="/" class="mt-md inline-block text-primary hover:underline">Browse courses</RouterLink>
      </div>
    </div>
  </main>
</template>
