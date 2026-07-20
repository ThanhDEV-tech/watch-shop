<script setup>
import { reactive, ref, watch } from 'vue'
import BaseModal from '../ui/BaseModal.vue'

const props = defineProps({
  brand: { type: Object, default: null },
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
})

const emit = defineEmits(['close', 'submit'])

const form = reactive({
  name: '',
  slug: '',
  description: '',
  logo: '',
  country: '',
  is_active: true,
})
const slugTouched = ref(false)

const slugify = (value) => value
  .normalize('NFD')
  .replace(/[\u0300-\u036f]/g, '')
  .replace(/đ/g, 'd')
  .replace(/Đ/g, 'D')
  .toLowerCase()
  .trim()
  .replace(/[^a-z0-9]+/g, '-')
  .replace(/^-+|-+$/g, '')

watch(() => props.brand, (brand) => {
  form.name = brand?.name ?? ''
  form.slug = brand?.slug ?? ''
  form.description = brand?.description ?? ''
  form.logo = brand?.logo ?? ''
  form.country = brand?.country ?? ''
  form.is_active = brand?.is_active ?? true
  slugTouched.value = Boolean(brand?.slug)
}, { immediate: true })

watch(() => form.name, (name) => {
  if (!slugTouched.value) form.slug = slugify(name)
})

const submit = () => {
  emit('submit', {
    ...form,
    slug: form.slug || slugify(form.name),
  })
}
</script>

<template>
  <BaseModal max-width="xl" aria-labelledby="brand-form-title" @close="emit('close')">
    <form class="w-full min-w-0 overflow-hidden rounded-xl border border-surface-variant bg-background shadow-2xl" @submit.prevent="submit">
      <header class="flex w-full min-w-0 items-center justify-between gap-md border-b border-surface-variant bg-surface px-md py-sm">
        <div class="w-full min-w-0">
          <p class="w-full font-mono text-[11px] uppercase tracking-widest text-primary">Brand editor</p>
          <h2 id="brand-form-title" class="w-full font-display text-headline-sm font-semibold text-on-surface">
            {{ brand ? 'Sửa thương hiệu' : 'Thêm thương hiệu' }}
          </h2>
        </div>
        <button class="material-symbols-outlined shrink-0 p-2 text-on-surface-variant hover:text-on-surface" type="button" @click="emit('close')">close</button>
      </header>

      <div class="w-full min-w-0 space-y-md p-md">
        <div class="grid w-full min-w-0 gap-md md:grid-cols-2">
          <div class="w-full min-w-0">
            <label class="block w-full text-body-sm text-on-surface" for="brand-name">Tên thương hiệu</label>
            <input id="brand-name" v-model.trim="form.name" required maxlength="255" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary" />
          </div>

          <div class="w-full min-w-0">
            <label class="block w-full text-body-sm text-on-surface" for="brand-slug">Slug</label>
            <input id="brand-slug" v-model.trim="form.slug" required maxlength="255" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 font-mono text-body-sm text-on-surface outline-none focus:border-primary" @input="slugTouched = true" />
          </div>
        </div>

        <div class="w-full min-w-0">
          <label class="block w-full text-body-sm text-on-surface" for="brand-description">Mô tả</label>
          <textarea id="brand-description" v-model.trim="form.description" rows="4" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary"></textarea>
        </div>

        <div class="grid w-full min-w-0 gap-md md:grid-cols-[1fr_0.85fr]">
          <div class="w-full min-w-0">
            <label class="block w-full text-body-sm text-on-surface" for="brand-logo">Logo URL</label>
            <input id="brand-logo" v-model.trim="form.logo" maxlength="255" placeholder="https://..." class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary" />
          </div>

          <div class="w-full min-w-0">
            <label class="block w-full text-body-sm text-on-surface" for="brand-country">Quốc gia</label>
            <input id="brand-country" v-model.trim="form.country" maxlength="255" placeholder="Ví dụ: Việt Nam" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary" />
          </div>
        </div>

        <div v-if="form.logo" class="flex w-full min-w-0 items-center gap-sm rounded-lg border border-surface-variant bg-surface-container-lowest p-sm">
          <img :src="form.logo" alt="Xem trước logo" class="h-12 w-20 shrink-0 rounded bg-white object-contain p-2" />
          <p class="w-full min-w-0 text-body-sm text-on-surface-variant">Xem trước logo thương hiệu.</p>
        </div>

        <label class="flex w-full min-w-0 items-center gap-sm text-body-sm text-on-surface">
          <input v-model="form.is_active" type="checkbox" class="h-4 w-4 accent-[var(--accent-primary)]" />
          <span class="min-w-0">Hiển thị thương hiệu trên site</span>
        </label>

        <p v-if="error" class="w-full rounded-lg border border-error/40 bg-error/10 p-sm text-body-sm text-error">{{ error }}</p>
      </div>

      <footer class="flex w-full min-w-0 flex-col-reverse gap-sm border-t border-surface-variant p-md sm:flex-row sm:justify-end">
        <button class="w-full rounded-lg border border-surface-variant px-md py-3 text-body-sm text-on-surface sm:w-auto" type="button" :disabled="loading" @click="emit('close')">Hủy</button>
        <button class="w-full rounded-lg bg-primary-container px-md py-3 font-display text-body-sm font-semibold text-on-primary-container disabled:opacity-50 sm:w-auto" type="submit" :disabled="loading">
          {{ loading ? 'Đang lưu...' : 'Lưu thương hiệu' }}
        </button>
      </footer>
    </form>
  </BaseModal>
</template>
