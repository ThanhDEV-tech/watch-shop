<script setup>
import { formatCurrency } from '../utils/formatCurrency'

defineProps({
  item: { type: Object, required: true },
})

defineEmits(['remove'])
</script>

<template>
  <article class="glass-card flex w-full min-w-0 flex-col sm:flex-row gap-md p-md rounded-lg group transition-all duration-300 hover:bg-[#25354e]">
    <div class="w-full sm:w-48 h-32 rounded-lg overflow-hidden shrink-0 border border-outline-variant">
      <img class="w-full h-full object-cover" :src="item.thumbnail" :alt="item.title" />
    </div>
    <div class="flex w-full min-w-0 flex-1 flex-col justify-between py-xs">
      <div class="w-full min-w-0">
        <div class="flex w-full min-w-0 justify-between items-start gap-sm">
          <h3 class="min-w-0 font-display text-headline-sm font-semibold mb-1 group-hover:text-primary transition-colors">{{ item.title }}</h3>
          <button class="shrink-0 cursor-pointer rounded text-on-surface-variant hover:text-error transition-colors flex items-center gap-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background" type="button" aria-label="Remove course" @click="$emit('remove', item.id)">
            <span class="material-symbols-outlined text-[20px]">delete</span>
          </button>
        </div>
        <p class="w-full text-body-sm text-on-surface-variant">By {{ item.instructor }} • {{ item.instructorRole }}</p>
        <div class="flex flex-wrap gap-xs mt-sm">
          <span v-if="item.bestseller" class="bg-surface-container-highest text-primary px-2 py-0.5 rounded text-[10px] font-mono uppercase tracking-wider">Bestseller</span>
          <span class="bg-surface-container-highest text-on-surface-variant px-2 py-0.5 rounded text-[10px] font-mono">{{ item.tag }}</span>
        </div>
      </div>
      <div class="flex w-full flex-wrap justify-between items-end gap-sm mt-md">
        <div class="flex min-w-0 items-center gap-xs text-tertiary">
          <span class="material-symbols-outlined text-[18px] filled-star">star</span>
          <span class="font-mono text-body-sm">{{ item.rating }} ({{ item.reviewCount }} reviews)</span>
        </div>
        <div class="flex min-w-0 shrink-0 flex-col items-end">
          <span v-if="item.originalPrice !== item.price" class="text-on-surface-variant line-through text-body-sm">{{ formatCurrency(item.originalPrice) }}</span>
          <span class="font-mono text-headline-sm text-primary">{{ formatCurrency(item.price) }}</span>
        </div>
      </div>
    </div>
  </article>
</template>
