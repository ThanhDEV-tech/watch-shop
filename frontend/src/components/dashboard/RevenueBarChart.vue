<script setup>
import { computed } from 'vue'
import { formatCurrency } from '../../utils/formatCurrency'

const props = defineProps({
  data: { type: Array, default: () => [] },
})

const maxValue = computed(() => Math.max(...props.data.map((item) => Number(item.total)), 1))
const shortDate = (date) => new Intl.DateTimeFormat('vi-VN', { day: '2-digit', month: '2-digit' }).format(new Date(`${date}T00:00:00`))
</script>

<template>
  <div class="w-full min-w-0 overflow-x-auto">
    <div class="flex h-64 min-w-[560px] items-end gap-sm border-b border-l border-surface-variant px-sm pt-md">
      <div v-for="item in data" :key="item.date" class="group flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-xs">
        <span class="w-full truncate text-center font-mono text-[10px] text-on-surface-variant opacity-0 transition-opacity group-hover:opacity-100">
          {{ formatCurrency(item.total) }}
        </span>
        <div
          class="w-full max-w-12 rounded-t-md bg-primary/80 transition-colors group-hover:bg-primary"
          :style="{ height: `${Math.max((Number(item.total) / maxValue) * 180, Number(item.total) ? 8 : 2)}px` }"
          :title="`${item.date}: ${formatCurrency(item.total)}`"
        ></div>
        <span class="shrink-0 font-mono text-[11px] text-on-surface-variant">{{ shortDate(item.date) }}</span>
      </div>
    </div>
  </div>
</template>
