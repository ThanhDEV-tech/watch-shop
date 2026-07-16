<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import ChapterFormModal from '../../components/instructor/ChapterFormModal.vue'
import LessonFormModal from '../../components/instructor/LessonFormModal.vue'
import {
  createInstructorChapter,
  createInstructorLesson,
  deleteInstructorChapter,
  deleteInstructorLesson,
  getCourseChapters,
  getInstructorCourses,
  reorderInstructorChapters,
  reorderInstructorLessons,
  updateInstructorChapter,
  updateInstructorLesson,
} from '../../services/api'

const route = useRoute()
const course = ref(null)
const chapters = ref([])
const expandedIds = ref(new Set())
const loading = ref(true)
const error = ref('')
const modalError = ref('')
const saving = ref(false)
const chapterModalOpen = ref(false)
const editingChapter = ref(null)
const lessonModalOpen = ref(false)
const editingLesson = ref(null)
const targetChapterId = ref(null)
const draggedChapterIndex = ref(null)
const draggedLesson = ref(null)
const reordering = ref(false)

const responseError = (requestError, fallback) => {
  const errors = requestError.response?.data?.data?.errors
  return errors ? Object.values(errors).flat()[0] : (requestError.response?.data?.message ?? fallback)
}

const fetchCurriculum = async () => {
  loading.value = true
  error.value = ''
  try {
    const [coursesResponse, chaptersResponse] = await Promise.all([
      getInstructorCourses(),
      getCourseChapters(route.params.id),
    ])
    course.value = (coursesResponse.data.data ?? []).find((item) => item.id === Number(route.params.id)) ?? null
    if (!course.value) throw new Error('Không tìm thấy khóa học hoặc bạn không phải chủ sở hữu.')
    chapters.value = chaptersResponse.data.data ?? []
    expandedIds.value = new Set(chapters.value.map((chapter) => chapter.id))
  } catch (requestError) {
    error.value = requestError.response?.data?.message ?? requestError.message ?? 'Không thể tải curriculum.'
  } finally {
    loading.value = false
  }
}

const toggleChapter = (chapterId) => {
  const next = new Set(expandedIds.value)
  if (next.has(chapterId)) next.delete(chapterId)
  else next.add(chapterId)
  expandedIds.value = next
}

const openChapterCreate = () => { editingChapter.value = null; modalError.value = ''; chapterModalOpen.value = true }
const openChapterEdit = (chapter) => { editingChapter.value = chapter; modalError.value = ''; chapterModalOpen.value = true }
const openLessonCreate = (chapter) => { targetChapterId.value = chapter.id; editingLesson.value = null; modalError.value = ''; lessonModalOpen.value = true }
const openLessonEdit = (lesson) => { targetChapterId.value = lesson.chapter_id; editingLesson.value = lesson; modalError.value = ''; lessonModalOpen.value = true }

const saveChapter = async (payload) => {
  saving.value = true
  modalError.value = ''
  try {
    if (editingChapter.value) await updateInstructorChapter(editingChapter.value.id, payload)
    else await createInstructorChapter(route.params.id, payload)
    chapterModalOpen.value = false
    await fetchCurriculum()
  } catch (requestError) {
    modalError.value = responseError(requestError, 'Không thể lưu chapter.')
  } finally { saving.value = false }
}

const saveLesson = async (payload) => {
  saving.value = true
  modalError.value = ''
  try {
    if (editingLesson.value) await updateInstructorLesson(editingLesson.value.id, payload)
    else await createInstructorLesson(targetChapterId.value, payload)
    lessonModalOpen.value = false
    await fetchCurriculum()
  } catch (requestError) {
    modalError.value = responseError(requestError, 'Không thể lưu lesson.')
  } finally { saving.value = false }
}

const removeChapter = async (chapter) => {
  if (!window.confirm(`Xóa chapter “${chapter.title}” và toàn bộ lesson bên trong?`)) return
  error.value = ''
  try { await deleteInstructorChapter(chapter.id); await fetchCurriculum() }
  catch (requestError) { error.value = responseError(requestError, 'Không thể xóa chapter.') }
}

const removeLesson = async (lesson) => {
  if (!window.confirm(`Xóa lesson “${lesson.title}”?`)) return
  error.value = ''
  try { await deleteInstructorLesson(lesson.id); await fetchCurriculum() }
  catch (requestError) { error.value = responseError(requestError, 'Không thể xóa lesson.') }
}

const startChapterDrag = (chapterIndex, event) => {
  draggedChapterIndex.value = chapterIndex
  event.dataTransfer.effectAllowed = 'move'
  event.dataTransfer.setData('text/plain', `chapter:${chapters.value[chapterIndex].id}`)
}

const dropChapter = async (targetIndex) => {
  const sourceIndex = draggedChapterIndex.value
  draggedChapterIndex.value = null
  if (sourceIndex === null || sourceIndex === targetIndex || reordering.value) return

  const previous = [...chapters.value]
  const [movedChapter] = chapters.value.splice(sourceIndex, 1)
  chapters.value.splice(targetIndex, 0, movedChapter)
  chapters.value.forEach((chapter, index) => { chapter.position = index + 1 })

  reordering.value = true
  error.value = ''
  try {
    const items = chapters.value.map(({ id, position }) => ({ id, position }))
    const response = await reorderInstructorChapters(route.params.id, items)
    chapters.value = response.data.data ?? chapters.value
  } catch (requestError) {
    chapters.value = previous
    error.value = responseError(requestError, 'Không thể cập nhật thứ tự chapter.')
    await fetchCurriculum()
  } finally {
    reordering.value = false
  }
}

const startLessonDrag = (chapter, lessonIndex, event) => {
  draggedLesson.value = { chapterId: chapter.id, lessonIndex }
  event.dataTransfer.effectAllowed = 'move'
  event.dataTransfer.setData('text/plain', `lesson:${chapter.lessons[lessonIndex].id}`)
}

const dropLesson = async (chapter, targetIndex) => {
  const source = draggedLesson.value
  draggedLesson.value = null
  if (!source || source.chapterId !== chapter.id || source.lessonIndex === targetIndex || reordering.value) return

  const previous = [...chapter.lessons]
  const [movedLesson] = chapter.lessons.splice(source.lessonIndex, 1)
  chapter.lessons.splice(targetIndex, 0, movedLesson)
  chapter.lessons.forEach((lesson, index) => { lesson.position = index + 1 })

  reordering.value = true
  error.value = ''
  try {
    const items = chapter.lessons.map(({ id, position }) => ({ id, position }))
    const response = await reorderInstructorLessons(chapter.id, items)
    chapter.lessons = response.data.data ?? chapter.lessons
  } catch (requestError) {
    chapter.lessons = previous
    error.value = responseError(requestError, 'Không thể cập nhật thứ tự lesson.')
    await fetchCurriculum()
  } finally {
    reordering.value = false
  }
}

onMounted(fetchCurriculum)
</script>

<template>
  <main class="mx-auto w-full min-w-0 max-w-5xl p-margin-mobile md:p-gutter lg:p-lg">
    <RouterLink to="/instructor/courses" class="inline-flex items-center gap-xs text-body-sm text-primary hover:underline"><span class="material-symbols-outlined text-[18px]">arrow_back</span> Khóa học của tôi</RouterLink>
    <div class="mt-md flex w-full min-w-0 flex-wrap items-end justify-between gap-md">
      <div class="w-full min-w-0 sm:w-auto"><p class="w-full font-mono text-label-mono uppercase tracking-widest text-primary">Curriculum builder</p><h1 class="mt-xs w-full font-display text-headline-md font-bold text-on-surface">{{ course?.title ?? 'Quản lý nội dung' }}</h1><p class="mt-xs w-full text-body-sm text-on-surface-variant">Sắp xếp chapter và xây dựng lesson cho khóa học.</p></div>
      <button class="w-full rounded-lg bg-primary-container px-md py-3 font-display text-body-sm font-semibold text-on-primary-container hover:opacity-90 sm:w-auto" type="button" @click="openChapterCreate">Thêm chapter</button>
    </div>

    <p v-if="error" class="mt-md w-full rounded-lg border border-error/40 bg-error/10 p-md text-body-sm text-error">{{ error }}</p>
    <div v-if="loading" class="mt-lg space-y-sm"><div v-for="index in 3" :key="index" class="h-28 w-full animate-pulse rounded-lg border border-surface-variant bg-surface"></div></div>
    <div v-else-if="!chapters.length" class="mt-lg flex min-h-[280px] w-full min-w-0 items-center justify-center rounded-lg border border-surface-variant bg-surface text-center"><div class="w-full min-w-0 max-w-[28rem] p-md"><span class="material-symbols-outlined text-6xl text-primary">format_list_numbered</span><h2 class="mt-sm w-full font-display text-headline-sm text-on-surface">Chưa có chapter</h2><p class="mt-xs w-full text-body-sm text-on-surface-variant">Thêm chapter đầu tiên, sau đó tạo ít nhất một lesson để có thể gửi duyệt.</p><button class="mt-md w-full rounded-lg bg-primary-container px-md py-3 text-body-sm font-semibold text-on-primary-container" type="button" @click="openChapterCreate">Thêm chapter đầu tiên</button></div></div>

    <div v-else class="mt-lg w-full min-w-0 space-y-sm">
      <article v-for="(chapter, chapterIndex) in chapters" :key="chapter.id" class="w-full min-w-0 overflow-hidden rounded-lg border border-surface-variant bg-surface" @dragover.prevent @drop.prevent="dropChapter(chapterIndex)">
        <div class="flex w-full min-w-0 flex-wrap items-center gap-sm p-md">
          <button draggable="true" class="material-symbols-outlined shrink-0 cursor-grab rounded p-xs text-on-surface-variant hover:bg-surface-container-highest hover:text-primary active:cursor-grabbing" type="button" aria-label="Kéo để sắp xếp chapter" title="Kéo để sắp xếp" @dragstart.stop="startChapterDrag(chapterIndex, $event)" @dragend="draggedChapterIndex = null">drag_indicator</button>
          <button class="flex min-w-0 flex-1 items-center gap-sm text-left" type="button" @click="toggleChapter(chapter.id)"><span class="font-mono text-xs text-primary">{{ String(chapterIndex + 1).padStart(2, '0') }}</span><div class="w-full min-w-0"><h2 class="w-full font-display text-body-md font-semibold text-on-surface">{{ chapter.title }}</h2><p class="w-full text-xs text-on-surface-variant">{{ chapter.lessons?.length ?? 0 }} lessons</p></div><span class="material-symbols-outlined shrink-0 text-on-surface-variant" :class="{ 'rotate-180': expandedIds.has(chapter.id) }">expand_more</span></button>
          <div class="flex shrink-0 items-center gap-xs"><button class="rounded-lg border border-surface-variant px-sm py-2 text-xs text-on-surface hover:border-primary" type="button" @click="openChapterEdit(chapter)">Sửa</button><button class="rounded-lg border border-error/30 px-sm py-2 text-xs text-error hover:bg-error/10" type="button" @click="removeChapter(chapter)">Xóa</button></div>
        </div>

        <div v-show="expandedIds.has(chapter.id)" class="w-full min-w-0 border-t border-surface-variant p-md">
          <div class="flex w-full min-w-0 items-center justify-between gap-md"><p class="min-w-0 text-body-sm text-on-surface-variant">Danh sách lesson</p><button class="shrink-0 rounded-lg border border-primary/40 px-sm py-2 text-xs text-primary hover:bg-primary/10" type="button" @click="openLessonCreate(chapter)">Thêm lesson</button></div>
          <div v-if="chapter.lessons?.length" class="mt-sm w-full min-w-0 divide-y divide-surface-variant rounded-lg border border-surface-variant bg-background">
            <div v-for="(lesson, lessonIndex) in chapter.lessons" :key="lesson.id" class="flex w-full min-w-0 flex-wrap items-center gap-sm p-sm" @dragover.stop.prevent @drop.stop.prevent="dropLesson(chapter, lessonIndex)">
              <button draggable="true" class="material-symbols-outlined shrink-0 cursor-grab rounded p-xs text-on-surface-variant hover:bg-surface-container-highest hover:text-primary active:cursor-grabbing" type="button" aria-label="Kéo để sắp xếp lesson" title="Kéo để sắp xếp" @dragstart.stop="startLessonDrag(chapter, lessonIndex, $event)" @dragend="draggedLesson = null">drag_indicator</button><span class="material-symbols-outlined shrink-0 text-primary">play_circle</span><div class="w-full min-w-0 flex-1"><p class="w-full text-body-sm font-medium text-on-surface">{{ lesson.title }}</p><p class="w-full font-mono text-[11px] text-on-surface-variant">{{ Math.ceil(Number(lesson.duration_seconds) / 60) }} phút · {{ lesson.is_free_preview ? 'Free preview' : 'Locked' }}</p></div><div class="flex shrink-0 gap-xs"><button class="rounded-lg border border-surface-variant px-sm py-2 text-xs text-on-surface hover:border-primary" type="button" @click="openLessonEdit(lesson)">Sửa</button><button class="rounded-lg border border-error/30 px-sm py-2 text-xs text-error hover:bg-error/10" type="button" @click="removeLesson(lesson)">Xóa</button></div>
            </div>
          </div>
          <p v-else class="mt-sm w-full rounded-lg border border-dashed border-surface-variant p-md text-center text-body-sm text-on-surface-variant">Chapter này chưa có lesson.</p>
        </div>
      </article>
    </div>

    <ChapterFormModal v-if="chapterModalOpen" :chapter="editingChapter" :loading="saving" :error="modalError" @close="chapterModalOpen = false" @submit="saveChapter" />
    <LessonFormModal v-if="lessonModalOpen" :lesson="editingLesson" :loading="saving" :error="modalError" @close="lessonModalOpen = false" @submit="saveLesson" />
  </main>
</template>
