<script setup>
import { reactive, watch } from 'vue'
import BaseModal from '../ui/BaseModal.vue'

const props = defineProps({
  chapter: { type: Object, default: null },
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
})

const emit = defineEmits(['close', 'submit'])
const form = reactive({ title: '', description: '' })

watch(() => props.chapter, (chapter) => {
  form.title = chapter?.title ?? ''
  form.description = chapter?.description ?? ''
}, { immediate: true })
</script>

<template>
  <BaseModal max-width="md" aria-labelledby="chapter-form-title" @close="emit('close')">
      <form class="w-full min-w-0 overflow-hidden rounded-xl border border-surface-variant bg-background shadow-2xl" @submit.prevent="emit('submit', { ...form })">
        <header class="flex w-full min-w-0 items-center justify-between gap-md border-b border-surface-variant bg-surface p-md">
          <div class="w-full min-w-0"><p class="w-full font-mono text-[11px] uppercase tracking-widest text-primary">Curriculum</p><h2 id="chapter-form-title" class="w-full font-display text-headline-sm font-semibold text-on-surface">{{ chapter ? 'Sửa chapter' : 'Thêm chapter' }}</h2></div>
          <button class="material-symbols-outlined h-10 w-10 shrink-0 rounded-lg text-on-surface-variant hover:bg-surface-container-highest" type="button" aria-label="Đóng" @click="emit('close')">close</button>
        </header>
        <div class="w-full min-w-0 space-y-md p-md">
          <div class="w-full min-w-0"><label class="block w-full text-body-sm text-on-surface" for="chapter-title">Tiêu đề</label><input id="chapter-title" v-model.trim="form.title" required maxlength="255" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary" /></div>
          <div class="w-full min-w-0"><label class="block w-full text-body-sm text-on-surface" for="chapter-description">Mô tả</label><textarea id="chapter-description" v-model.trim="form.description" rows="3" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary"></textarea></div>
          <p v-if="error" class="w-full rounded-lg border border-error/40 bg-error/10 p-sm text-body-sm text-error">{{ error }}</p>
        </div>
        <footer class="flex w-full min-w-0 flex-col-reverse gap-sm border-t border-surface-variant p-md sm:flex-row sm:justify-end"><button class="w-full rounded-lg border border-surface-variant px-md py-3 text-body-sm text-on-surface sm:w-auto" type="button" :disabled="loading" @click="emit('close')">Hủy</button><button class="w-full rounded-lg bg-primary-container px-md py-3 font-display text-body-sm font-semibold text-on-primary-container disabled:opacity-50 sm:w-auto" type="submit" :disabled="loading">{{ loading ? 'Đang lưu...' : 'Lưu chapter' }}</button></footer>
      </form>
  </BaseModal>
</template>
