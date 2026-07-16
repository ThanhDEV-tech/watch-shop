<script setup>
import { reactive, watch } from 'vue'
import BaseModal from '../ui/BaseModal.vue'

const props = defineProps({
  lesson: { type: Object, default: null },
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
})

const emit = defineEmits(['close', 'submit'])
const form = reactive({ title: '', content: '', youtube_url: '', duration_minutes: 0, is_free_preview: false })

watch(() => props.lesson, (lesson) => {
  form.title = lesson?.title ?? ''
  form.content = lesson?.content ?? ''
  form.youtube_url = lesson?.youtube_url ?? ''
  form.duration_minutes = lesson ? Math.ceil(Number(lesson.duration_seconds ?? 0) / 60) : 0
  form.is_free_preview = lesson?.is_free_preview ?? false
}, { immediate: true })

const submit = () => emit('submit', {
  title: form.title,
  content: form.content || null,
  youtube_url: form.youtube_url || null,
  duration_seconds: Math.max(0, Number(form.duration_minutes) * 60),
  is_free_preview: form.is_free_preview,
})
</script>

<template>
  <BaseModal max-width="xl" aria-labelledby="lesson-form-title" @close="emit('close')">
      <form class="flex max-h-[92vh] w-full min-w-0 flex-col overflow-hidden rounded-xl border border-surface-variant bg-background shadow-2xl" @submit.prevent="submit">
        <header class="flex w-full min-w-0 items-center justify-between gap-md border-b border-surface-variant bg-surface p-md">
          <div class="w-full min-w-0"><p class="w-full font-mono text-[11px] uppercase tracking-widest text-primary">Lesson editor</p><h2 id="lesson-form-title" class="w-full font-display text-headline-sm font-semibold text-on-surface">{{ lesson ? 'Sửa lesson' : 'Thêm lesson' }}</h2></div>
          <button class="material-symbols-outlined h-10 w-10 shrink-0 rounded-lg text-on-surface-variant hover:bg-surface-container-highest" type="button" aria-label="Đóng" @click="emit('close')">close</button>
        </header>
        <div class="w-full min-w-0 flex-1 space-y-md overflow-y-auto p-md">
          <div class="w-full min-w-0"><label class="block w-full text-body-sm text-on-surface" for="lesson-title">Tiêu đề</label><input id="lesson-title" v-model.trim="form.title" required maxlength="255" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary" /></div>
          <div class="w-full min-w-0"><label class="block w-full text-body-sm text-on-surface" for="lesson-content">Nội dung bài học</label><textarea id="lesson-content" v-model="form.content" rows="8" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary"></textarea></div>
          <div class="w-full min-w-0"><label class="block w-full text-body-sm text-on-surface" for="lesson-youtube">YouTube URL</label><input id="lesson-youtube" v-model.trim="form.youtube_url" type="url" maxlength="255" placeholder="https://www.youtube.com/watch?v=..." class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary" /></div>
          <div class="w-full min-w-0"><label class="block w-full text-body-sm text-on-surface" for="lesson-duration">Thời lượng (phút)</label><input id="lesson-duration" v-model.number="form.duration_minutes" min="0" type="number" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary" /></div>
          <label class="flex w-full min-w-0 items-center gap-sm text-body-sm text-on-surface"><input v-model="form.is_free_preview" type="checkbox" class="h-4 w-4 shrink-0 accent-[var(--accent-primary)]" /><span class="min-w-0">Cho phép xem trước miễn phí</span></label>
          <p v-if="error" class="w-full rounded-lg border border-error/40 bg-error/10 p-sm text-body-sm text-error">{{ error }}</p>
        </div>
        <footer class="flex w-full min-w-0 flex-col-reverse gap-sm border-t border-surface-variant p-md sm:flex-row sm:justify-end"><button class="w-full rounded-lg border border-surface-variant px-md py-3 text-body-sm text-on-surface sm:w-auto" type="button" :disabled="loading" @click="emit('close')">Hủy</button><button class="w-full rounded-lg bg-primary-container px-md py-3 font-display text-body-sm font-semibold text-on-primary-container disabled:opacity-50 sm:w-auto" type="submit" :disabled="loading">{{ loading ? 'Đang lưu...' : 'Lưu lesson' }}</button></footer>
      </form>
  </BaseModal>
</template>
