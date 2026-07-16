<script setup>
import { reactive, watch } from 'vue'
import BaseModal from '../ui/BaseModal.vue'

const props = defineProps({
  category: { type: Object, default: null },
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
})

const emit = defineEmits(['close', 'submit'])
const accentPalette = ['#FF6B4A', '#4ADE80', '#38BDF8', '#A78BFA', '#F472B6', '#FBBF24']
const randomAccentColor = () => accentPalette[Math.floor(Math.random() * accentPalette.length)]
const form = reactive({ name: '', description: '', icon: '', accent_color: '#FF6B4A', is_active: true })

watch(() => props.category, (category) => {
  form.name = category?.name ?? ''
  form.description = category?.description ?? ''
  form.icon = category?.icon ?? ''
  form.accent_color = category?.accent_color ?? randomAccentColor()
  form.is_active = category?.is_active ?? true
}, { immediate: true })
</script>

<template>
  <BaseModal max-width="lg" aria-labelledby="category-form-title" @close="emit('close')">
    <form class="w-full min-w-0 overflow-hidden rounded-xl border border-surface-variant bg-background shadow-2xl" @submit.prevent="emit('submit', { ...form })">
      <header class="flex w-full min-w-0 items-center justify-between gap-md border-b border-surface-variant bg-surface px-md py-sm">
        <div class="w-full min-w-0">
          <p class="w-full font-mono text-[11px] uppercase tracking-widest text-primary">Category editor</p>
          <h2 id="category-form-title" class="w-full font-display text-headline-sm font-semibold text-on-surface">
            {{ category ? 'Sửa danh mục' : 'Thêm danh mục' }}
          </h2>
        </div>
        <button class="material-symbols-outlined shrink-0 p-2 text-on-surface-variant hover:text-on-surface" type="button" @click="emit('close')">close</button>
      </header>

      <div class="w-full min-w-0 space-y-md p-md">
        <div class="w-full min-w-0">
          <label class="block w-full text-body-sm text-on-surface" for="category-name">Tên danh mục</label>
          <input id="category-name" v-model.trim="form.name" required maxlength="255" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary" />
        </div>

        <div class="w-full min-w-0">
          <label class="block w-full text-body-sm text-on-surface" for="category-description">Mô tả</label>
          <textarea id="category-description" v-model.trim="form.description" rows="4" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary"></textarea>
        </div>

        <div class="w-full min-w-0">
          <label class="block w-full text-body-sm text-on-surface" for="category-icon">Material icon (không bắt buộc)</label>
          <input id="category-icon" v-model.trim="form.icon" maxlength="255" placeholder="Ví dụ: terminal" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary" />
        </div>

        <div class="w-full min-w-0">
          <label class="block w-full text-body-sm text-on-surface" for="category-accent">Màu accent</label>
          <div class="mt-xs flex w-full min-w-0 items-center gap-sm">
            <input id="category-accent" v-model="form.accent_color" type="color" class="h-12 w-16 shrink-0 cursor-pointer rounded-lg border border-surface-variant bg-surface p-1" />
            <input v-model.trim="form.accent_color" maxlength="7" class="w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 font-mono text-body-sm text-on-surface outline-none focus:border-primary" />
          </div>
        </div>

        <label class="flex w-full min-w-0 items-center gap-sm text-body-sm text-on-surface">
          <input v-model="form.is_active" type="checkbox" class="h-4 w-4 accent-[var(--accent-primary)]" />
          <span class="min-w-0">Hiển thị danh mục trên site</span>
        </label>

        <p v-if="error" class="w-full rounded-lg border border-error/40 bg-error/10 p-sm text-body-sm text-error">{{ error }}</p>
      </div>

      <footer class="flex w-full min-w-0 flex-col-reverse gap-sm border-t border-surface-variant p-md sm:flex-row sm:justify-end">
        <button class="w-full rounded-lg border border-surface-variant px-md py-3 text-body-sm text-on-surface sm:w-auto" type="button" :disabled="loading" @click="emit('close')">Hủy</button>
        <button class="w-full rounded-lg bg-primary-container px-md py-3 font-display text-body-sm font-semibold text-on-primary-container disabled:opacity-50 sm:w-auto" type="submit" :disabled="loading">
          {{ loading ? 'Đang lưu...' : 'Lưu danh mục' }}
        </button>
      </footer>
    </form>
  </BaseModal>
</template>
