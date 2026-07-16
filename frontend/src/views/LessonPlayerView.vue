<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ChatbotWidget from '../components/ChatbotWidget.vue'
import LessonComments from '../components/LessonComments.vue'
import { getCourseById, getCourseChapters, getLesson, markLessonComplete } from '../services/api'
import { useAuthStore } from '../stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const chapters = ref([])
const lesson = ref(null)
const expandedChapterIds = ref(new Set())
const isLoading = ref(true)
const isCompleting = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const isEnrolled = ref(false)

const flattenedLessons = computed(() => chapters.value.flatMap((chapter) => chapter.lessons ?? []))
const completedLessonsCount = computed(() => flattenedLessons.value.filter((item) => item.is_completed).length)
const progressPercent = computed(() => {
  if (!flattenedLessons.value.length) return 0
  return Math.round((completedLessonsCount.value / flattenedLessons.value.length) * 100)
})
const currentLessonIndex = computed(() => flattenedLessons.value.findIndex((item) => item.id === Number(route.params.lessonId)))
const previousLesson = computed(() => flattenedLessons.value[currentLessonIndex.value - 1] ?? null)
const nextLesson = computed(() => flattenedLessons.value[currentLessonIndex.value + 1] ?? null)
const currentOutlineLesson = computed(() => flattenedLessons.value[currentLessonIndex.value] ?? null)

const youtubeEmbedUrl = computed(() => {
  const url = lesson.value?.youtube_url
  if (!url) return ''

  try {
    const parsedUrl = new URL(url)
    let videoId = parsedUrl.searchParams.get('v')

    if (parsedUrl.hostname.includes('youtu.be')) videoId = parsedUrl.pathname.slice(1)
    if (parsedUrl.pathname.includes('/embed/')) videoId = parsedUrl.pathname.split('/embed/')[1]

    return videoId ? `https://www.youtube.com/embed/${videoId}` : ''
  } catch {
    return ''
  }
})

const toggleChapter = (chapterId) => {
  const nextExpanded = new Set(expandedChapterIds.value)
  if (nextExpanded.has(chapterId)) nextExpanded.delete(chapterId)
  else nextExpanded.add(chapterId)
  expandedChapterIds.value = nextExpanded
}

const goToLesson = (targetLesson) => {
  if (!targetLesson || targetLesson.id === Number(route.params.lessonId)) return

  router.push({
    name: 'lesson-player',
    params: { courseId: route.params.courseId, lessonId: targetLesson.id },
  })
}

const redirectDeniedAccess = async (message) => {
  await router.replace({
    name: 'course-detail',
    params: { id: route.params.courseId },
    query: { notice: message || 'Bạn cần mua khóa học trước khi xem bài học này.' },
  })
}

const fetchPlayerData = async () => {
  isLoading.value = true
  errorMessage.value = ''
  successMessage.value = ''
  lesson.value = null
  isEnrolled.value = false

  try {
    const [courseResponse, chaptersResponse, lessonResponse] = await Promise.all([
      getCourseById(route.params.courseId),
      getCourseChapters(route.params.courseId),
      getLesson(route.params.courseId, route.params.lessonId),
    ])

    isEnrolled.value = Boolean(courseResponse.data.data.is_enrolled)
    chapters.value = chaptersResponse.data.data ?? []
    lesson.value = lessonResponse.data.data

    const currentChapter = chapters.value.find((chapter) =>
      chapter.lessons?.some((item) => item.id === Number(route.params.lessonId)))
    expandedChapterIds.value = new Set(currentChapter ? [currentChapter.id] : chapters.value.slice(0, 1).map((chapter) => chapter.id))
  } catch (error) {
    if (error.response?.status === 403) {
      await redirectDeniedAccess(error.response?.data?.message)
      return
    }

    errorMessage.value = error.response?.data?.message ?? 'Không thể tải bài học này.'
  } finally {
    isLoading.value = false
  }
}

const completeCurrentLesson = async () => {
  if (!lesson.value || currentOutlineLesson.value?.is_completed || isCompleting.value) return

  isCompleting.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await markLessonComplete(lesson.value.id)
    const outlineLesson = currentOutlineLesson.value
    if (outlineLesson) outlineLesson.is_completed = true
    successMessage.value = `Đã hoàn thành bài học. Tiến độ khóa học: ${response.data.data.progress_percent}%.`
  } catch (error) {
    if (error.response?.status === 403) {
      await redirectDeniedAccess(error.response?.data?.message)
      return
    }

    errorMessage.value = error.response?.data?.message ?? 'Không thể cập nhật tiến độ bài học.'
  } finally {
    isCompleting.value = false
  }
}

watch(
  () => [route.params.courseId, route.params.lessonId],
  fetchPlayerData,
  { immediate: true },
)
</script>

<template>
  <main class="mx-auto min-h-[calc(100vh-5rem)] w-full max-w-[1600px] px-margin-mobile py-md md:px-gutter">
    <div v-if="isLoading" class="grid w-full min-w-0 grid-cols-1 gap-md lg:grid-cols-[320px_minmax(0,1fr)]">
      <div class="h-[36rem] animate-pulse rounded-lg bg-surface"></div>
      <div class="h-[42rem] animate-pulse rounded-lg bg-surface"></div>
    </div>

    <div v-else-if="errorMessage && !lesson" class="flex min-h-[400px] w-full min-w-0 items-center justify-center text-center">
      <div class="w-full min-w-0 max-w-md rounded-lg border border-error/40 bg-surface p-lg">
        <span class="material-symbols-outlined text-5xl text-error">error</span>
        <p class="mt-md w-full text-body-md text-on-surface">{{ errorMessage }}</p>
        <RouterLink :to="`/courses/${route.params.courseId}`" class="mt-md inline-block text-primary hover:underline">Back to course</RouterLink>
      </div>
    </div>

    <div v-else-if="lesson" class="grid w-full min-w-0 grid-cols-1 items-start gap-md lg:grid-cols-[320px_minmax(0,1fr)]">
      <aside class="w-full min-w-0 overflow-hidden rounded-lg border border-surface-variant bg-surface lg:sticky lg:top-24 lg:max-h-[calc(100vh-7rem)] lg:overflow-y-auto">
        <div class="w-full min-w-0 border-b border-surface-variant p-md">
          <p class="w-full font-mono text-label-mono uppercase tracking-widest text-primary">Course content</p>
          <div class="mt-xs flex w-full min-w-0 flex-wrap items-center justify-between gap-xs">
            <p class="min-w-0 text-body-sm text-on-surface-variant">{{ completedLessonsCount }} / {{ flattenedLessons.length }} lessons complete</p>
            <span v-if="progressPercent === 100" class="shrink-0 rounded border border-[var(--accent-success)]/40 bg-[var(--accent-success)]/10 px-2 py-1 font-mono text-[10px] font-medium uppercase text-[var(--accent-success)]">Đã hoàn thành</span>
          </div>
          <div class="mt-sm h-2 w-full overflow-hidden rounded-full bg-surface-container-highest" role="progressbar" aria-label="Tiến độ khóa học" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="progressPercent">
            <div class="h-full rounded-full bg-primary transition-[width] duration-300 ease-out" :style="{ width: `${progressPercent}%` }"></div>
          </div>
          <p class="mt-xs w-full text-right font-mono text-[10px] text-primary">{{ progressPercent }}%</p>
        </div>

        <div class="w-full min-w-0">
          <section v-for="(chapter, chapterIndex) in chapters" :key="chapter.id" class="w-full min-w-0 border-b border-surface-variant last:border-b-0">
            <button
              class="flex w-full min-w-0 items-center justify-between gap-sm p-md text-left transition-colors hover:bg-surface-container-highest"
              type="button"
              @click="toggleChapter(chapter.id)"
            >
              <div class="min-w-0 flex-1">
                <span class="font-mono text-[10px] text-primary">CHAPTER {{ String(chapterIndex + 1).padStart(2, '0') }}</span>
                <h2 class="mt-xs w-full min-w-0 font-display text-body-md font-semibold text-on-surface">{{ chapter.title }}</h2>
              </div>
              <span class="material-symbols-outlined shrink-0 text-on-surface-variant transition-transform" :class="expandedChapterIds.has(chapter.id) ? 'rotate-180' : ''">expand_more</span>
            </button>

            <div v-show="expandedChapterIds.has(chapter.id)" class="w-full min-w-0 pb-sm">
              <button
                v-for="chapterLesson in chapter.lessons"
                :key="chapterLesson.id"
                class="flex w-full min-w-0 items-center gap-sm border-l-2 px-md py-3 text-left transition-colors"
                :class="chapterLesson.id === Number(route.params.lessonId)
                  ? 'border-primary bg-primary/10 text-on-surface'
                  : 'border-transparent text-on-surface-variant hover:bg-surface-container-highest hover:text-on-surface'"
                type="button"
                @click="goToLesson(chapterLesson)"
              >
                <span
                  class="material-symbols-outlined shrink-0 text-[18px]"
                  :class="chapterLesson.is_completed ? 'text-[var(--accent-success)]' : 'text-on-surface-variant'"
                >
                  {{ chapterLesson.is_completed ? 'check_circle' : 'play_circle' }}
                </span>
                <span class="min-w-0 flex-1 text-body-sm leading-5">{{ chapterLesson.title }}</span>
                <span class="shrink-0 font-mono text-[10px]">{{ Math.ceil(chapterLesson.duration_seconds / 60) }}m</span>
              </button>
            </div>
          </section>
        </div>
      </aside>

      <article class="w-full min-w-0 space-y-md">
        <div class="aspect-video w-full overflow-hidden rounded-lg border border-surface-variant bg-surface-container-lowest">
          <iframe
            v-if="youtubeEmbedUrl"
            class="h-full w-full"
            :src="youtubeEmbedUrl"
            :title="lesson.title"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen
          ></iframe>
          <div v-else class="flex h-full w-full items-center justify-center text-center">
            <div class="w-full min-w-0 max-w-md p-md">
              <span class="material-symbols-outlined text-6xl text-primary">video_library</span>
              <p class="mt-sm w-full text-body-md text-on-surface-variant">Video for this lesson is being prepared.</p>
            </div>
          </div>
        </div>

        <div class="w-full min-w-0 rounded-lg border border-surface-variant bg-surface p-lg">
          <p class="w-full font-mono text-label-mono uppercase tracking-widest text-primary">Current lesson</p>
          <h1 class="mt-sm w-full min-w-0 font-display text-headline-md text-on-surface">{{ lesson.title }}</h1>

          <p v-if="successMessage" class="mt-md w-full rounded-lg border border-[var(--accent-success)]/40 bg-[var(--accent-success)]/10 p-md text-body-sm text-[var(--accent-success)]">
            {{ successMessage }}
          </p>
          <p v-if="errorMessage" class="mt-md w-full rounded-lg border border-error/40 bg-error/10 p-md text-body-sm text-error">
            {{ errorMessage }}
          </p>

          <div class="mt-lg w-full min-w-0 whitespace-pre-wrap text-body-md leading-7 text-on-surface-variant">{{ lesson.content || 'Lesson content is being updated.' }}</div>

          <div class="mt-lg flex w-full min-w-0 flex-col gap-sm border-t border-surface-variant pt-md sm:flex-row sm:items-center sm:justify-between">
            <button
              class="w-full rounded-lg border border-surface-variant px-md py-3 font-display text-button-text text-on-surface transition-colors hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-40 sm:w-auto"
              type="button"
              :disabled="!previousLesson"
              @click="goToLesson(previousLesson)"
            >
              ← Previous lesson
            </button>

            <button
              class="w-full rounded-lg bg-primary px-md py-3 font-display text-button-text font-semibold text-on-primary transition-all hover:-translate-y-0.5 hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
              type="button"
              :disabled="isCompleting || currentOutlineLesson?.is_completed"
              @click="completeCurrentLesson"
            >
              {{ currentOutlineLesson?.is_completed ? 'Completed' : (isCompleting ? 'Saving...' : 'Mark as Complete') }}
            </button>

            <button
              class="w-full rounded-lg border border-surface-variant px-md py-3 font-display text-button-text text-on-surface transition-colors hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-40 sm:w-auto"
              type="button"
              :disabled="!nextLesson"
              @click="goToLesson(nextLesson)"
            >
              Next lesson →
            </button>
          </div>
        </div>

        <LessonComments v-if="isEnrolled" :lesson-id="Number(route.params.lessonId)" />
        <section v-else class="flex min-h-40 w-full min-w-0 items-center justify-center rounded-lg border border-surface-variant bg-surface text-center"><div class="w-full min-w-0 max-w-md p-md"><span class="material-symbols-outlined text-5xl text-primary">lock</span><p class="mt-sm w-full text-body-md text-on-surface">Mua khóa học để tham gia bình luận.</p></div></section>
      </article>
    </div>
  </main>
  <ChatbotWidget v-if="authStore.isAuthenticated" :lesson-id="Number(route.params.lessonId)" />
</template>
