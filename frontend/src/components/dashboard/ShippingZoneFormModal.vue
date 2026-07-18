<script setup>
import { reactive, watch } from 'vue'
import BaseModal from '../ui/BaseModal.vue'

const props = defineProps({
  zone: { type: Object, default: null },
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
})

const emit = defineEmits(['close', 'submit'])

const form = reactive({
  name: '',
  fee: 0,
  display_order: 0,
  is_active: true,
})

watch(() => props.zone, (zone) => {
  form.name = zone?.name ?? ''
  form.fee = Number(zone?.fee ?? 0)
  form.display_order = Number(zone?.display_order ?? 0)
  form.is_active = zone?.is_active ?? true
}, { immediate: true })
</script>

<template>
  <BaseModal max-width="lg" aria-labelledby="shipping-zone-form-title" @close="emit('close')">
    <form class="w-full min-w-0 overflow-hidden rounded-xl border border-surface-variant bg-background shadow-2xl" @submit.prevent="emit('submit', { ...form })">
      <header class="flex w-full min-w-0 items-center justify-between gap-md border-b border-surface-variant bg-surface px-md py-sm">
        <div class="w-full min-w-0">
          <p class="w-full font-mono text-[11px] uppercase tracking-widest text-primary">Shipping zone editor</p>
          <h2 id="shipping-zone-form-title" class="w-full font-display text-headline-sm font-semibold text-on-surface">
            {{ zone ? 'Sửa khu vực giao hàng' : 'Thêm khu vực giao hàng' }}
          </h2>
        </div>
        <button class="material-symbols-outlined shrink-0 p-2 text-on-surface-variant hover:text-on-surface" type="button" aria-label="Đóng" @click="emit('close')">close</button>
      </header>

      <div class="w-full min-w-0 space-y-md p-md">
        <div class="w-full min-w-0">
          <label class="block w-full text-body-sm text-on-surface" for="shipping-zone-name">Tên khu vực</label>
          <input id="shipping-zone-name" v-model.trim="form.name" required maxlength="255" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary" placeholder="Ví dụ: Nội thành" />
        </div>

        <div class="grid w-full min-w-0 grid-cols-1 gap-md sm:grid-cols-2">
          <div class="w-full min-w-0">
            <label class="block w-full text-body-sm text-on-surface" for="shipping-zone-fee">Phí giao hàng</label>
            <input id="shipping-zone-fee" v-model.number="form.fee" required min="0" step="1000" type="number" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary" />
          </div>

          <div class="w-full min-w-0">
            <label class="block w-full text-body-sm text-on-surface" for="shipping-zone-order">Thứ tự hiển thị</label>
            <input id="shipping-zone-order" v-model.number="form.display_order" min="0" step="1" type="number" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary" />
          </div>
        </div>

        <label class="flex w-full min-w-0 items-center gap-sm text-body-sm text-on-surface">
          <input v-model="form.is_active" type="checkbox" class="h-4 w-4 accent-[var(--accent-primary)]" />
          <span class="min-w-0">Đang áp dụng cho checkout</span>
        </label>

        <p v-if="error" class="w-full rounded-lg border border-error/40 bg-error/10 p-sm text-body-sm text-error">{{ error }}</p>
      </div>

      <footer class="flex w-full min-w-0 flex-col-reverse gap-sm border-t border-surface-variant p-md sm:flex-row sm:justify-end">
        <button class="w-full rounded-lg border border-surface-variant px-md py-3 text-body-sm text-on-surface sm:w-auto" type="button" :disabled="loading" @click="emit('close')">Hủy</button>
        <button class="w-full rounded-lg bg-primary-container px-md py-3 font-display text-body-sm font-semibold text-on-primary-container disabled:opacity-50 sm:w-auto" type="submit" :disabled="loading">
          {{ loading ? 'Đang lưu...' : 'Lưu khu vực' }}
        </button>
      </footer>
    </form>
  </BaseModal>
</template>
