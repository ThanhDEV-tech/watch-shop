<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { getCategories } from '../services/api'

const emit = defineEmits(['select'])

const chipsScroller = ref(null)
const categories = ref([])
const activeCategoryId = ref(null)
const isLoading = ref(true)
const canScrollLeft = ref(false)
const canScrollRight = ref(false)

const selectCategory = (categoryId) => {
  activeCategoryId.value = categoryId
  emit('select', categoryId)
}

const updateScrollState = () => {
  const scroller = chipsScroller.value
  if (!scroller) return

  const maxScrollLeft = scroller.scrollWidth - scroller.clientWidth
  canScrollLeft.value = scroller.scrollLeft > 4
  canScrollRight.value = scroller.scrollLeft < maxScrollLeft - 4
}

const scrollChips = (direction) => {
  chipsScroller.value?.scrollBy({
    left: direction * 280,
    behavior: 'smooth',
  })
}

const categoryAccent = (category) => category.accent_color || '#FF6B4A'

const categoryChipStyle = (category) => {
  const accent = categoryAccent(category)

  if (activeCategoryId.value === category.id) {
    return {
      '--category-accent': accent,
      backgroundColor: accent,
      borderColor: accent,
      color: 'var(--text-primary)',
    }
  }

  return {
    '--category-accent': accent,
  }
}

onMounted(async () => {
  try {
    const response = await getCategories()
    categories.value = response.data.data.filter((category) => category.is_active)
  } finally {
    isLoading.value = false
    await nextTick()
    updateScrollState()
  }

  window.addEventListener('resize', updateScrollState)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', updateScrollState)
})
</script>

<template>
  <section id="categories" class="border-y border-surface-variant bg-surface-container-lowest font-body">
    <div class="mx-auto w-full min-w-0 max-w-container-max px-margin-mobile py-md md:px-gutter">
      <div class="relative w-full min-w-0">
        <button
          v-show="canScrollLeft"
          class="absolute left-0 top-1/2 z-10 hidden h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-surface-variant bg-surface text-on-surface shadow-[0_12px_24px_rgba(0,0,0,0.35)] transition-colors hover:border-primary hover:text-primary md:flex"
          type="button"
          aria-label="Cuộn danh mục sang trái"
          @click="scrollChips(-1)"
        >
          <span class="material-symbols-outlined text-[20px]">chevron_left</span>
        </button>

        <div class="slider-mask w-full min-w-0 overflow-hidden">
          <div
            ref="chipsScroller"
            class="no-scrollbar flex w-full min-w-0 items-center gap-sm overflow-x-auto scroll-smooth whitespace-nowrap px-1 pb-2"
            @scroll="updateScrollState"
          >
            <button
              class="shrink-0 rounded-full border px-md py-2.5 font-display text-button-text transition-colors"
              :class="activeCategoryId === null ? 'bg-primary text-on-primary font-bold border-primary' : 'bg-surface-container-low text-on-surface border-surface-variant hover:bg-surface-variant'"
              type="button"
              @click="selectCategory(null)"
            >
              All Courses
            </button>

            <span
              v-if="isLoading"
              class="h-10 w-36 shrink-0 animate-pulse rounded-full bg-surface-container-highest"
              aria-label="Loading categories"
            ></span>

            <button
              v-for="category in categories"
              v-else
              :key="category.id"
              class="category-chip shrink-0 rounded-full border px-md py-2.5 font-display text-button-text transition-colors"
              :class="activeCategoryId === category.id ? 'font-bold' : 'bg-surface-container-low text-on-surface border-surface-variant hover:bg-surface-variant'"
              :style="categoryChipStyle(category)"
              type="button"
              @click="selectCategory(category.id)"
            >
              {{ category.name }}
            </button>
          </div>
        </div>

        <button
          v-show="canScrollRight"
          class="absolute right-0 top-1/2 z-10 hidden h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-surface-variant bg-surface text-on-surface shadow-[0_12px_24px_rgba(0,0,0,0.35)] transition-colors hover:border-primary hover:text-primary md:flex"
          type="button"
          aria-label="Cuộn danh mục sang phải"
          @click="scrollChips(1)"
        >
          <span class="material-symbols-outlined text-[20px]">chevron_right</span>
        </button>
      </div>
    </div>
  </section>
</template>

<style scoped>
.category-chip {
  border-color: var(--border-subtle);
}

.category-chip:hover {
  border-color: var(--category-accent);
  color: var(--category-accent);
}

@media (prefers-reduced-motion: reduce) {
  .category-chip {
    transition: none;
  }
}
</style>
