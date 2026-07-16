<script setup>
defineProps({
  pagination: { type: Object, required: true },
})

defineEmits(['change'])
</script>

<template>
  <div v-if="pagination.last_page > 1" class="flex w-full min-w-0 items-center justify-between gap-md border-t border-surface-variant px-md py-sm">
    <p class="min-w-0 text-body-sm text-on-surface-variant">
      Trang {{ pagination.current_page }} / {{ pagination.last_page }} · {{ pagination.total }} mục
    </p>
    <div class="flex shrink-0 items-center gap-xs">
      <button
        class="cursor-pointer rounded-lg border border-surface-variant px-sm py-2 text-body-sm text-on-surface transition-colors hover:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-40"
        type="button"
        :disabled="pagination.current_page <= 1"
        @click="$emit('change', pagination.current_page - 1)"
      >
        Trước
      </button>
      <button
        class="cursor-pointer rounded-lg border border-surface-variant px-sm py-2 text-body-sm text-on-surface transition-colors hover:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-40"
        type="button"
        :disabled="pagination.current_page >= pagination.last_page"
        @click="$emit('change', pagination.current_page + 1)"
      >
        Sau
      </button>
    </div>
  </div>
</template>
