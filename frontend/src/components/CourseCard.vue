<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '../stores/cart'
import { formatCurrency } from '../utils/formatCurrency'

const router = useRouter()
const cartStore = useCartStore()

const props = defineProps({
  courseId: { type: [Number, String], default: null },
  thumbnail: { type: String, required: true },
  level: { type: String, required: true },
  techTag: { type: String, required: true },
  title: { type: String, required: true },
  instructor: { type: String, required: true },
  rating: { type: Number, required: true },
  price: { type: Number, required: true },
  description: { type: String, required: true },
  highlights: { type: Array, required: true },
  updatedAt: { type: String, default: 'Tháng 6/2026' },
  categoryAccent: { type: String, default: '#FF6B4A' },
})

const emit = defineEmits(['add-to-cart'])

const cartFeedback = ref('')
const cartFeedbackType = ref('success')

const categoryTagStyle = () => ({
  '--category-accent': props.categoryAccent || '#FF6B4A',
})

const addToCart = async () => {
  cartFeedback.value = ''

  try {
    const response = await cartStore.addItem(props.courseId)
    cartFeedbackType.value = 'success'
    cartFeedback.value = response.message
    emit('add-to-cart', response.data)
  } catch {
    cartFeedbackType.value = 'error'
    cartFeedback.value = cartStore.error
  }
}

const openCourse = () => {
  if (props.courseId) {
    router.push(`/courses/${props.courseId}`)
  }
}
</script>

<template>
  <div
    class="course-card-wrapper group relative h-[470px] w-[300px] shrink-0 cursor-pointer rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background"
    role="link"
    tabindex="0"
    @click="openCourse"
    @keydown.enter="openCourse"
  >
    <article class="course-card-element course-card-base flex h-full w-full min-w-0 flex-col overflow-hidden rounded-lg border border-surface-variant bg-surface-container-high font-body">
      <div class="relative aspect-video shrink-0 overflow-hidden">
        <img v-if="thumbnail" :alt="`${title} course`" class="h-full w-full object-cover" :src="thumbnail" />
        <div v-else class="flex h-full w-full items-center justify-center bg-surface-container-lowest text-primary">
          <span class="material-symbols-outlined text-5xl">school</span>
        </div>
        <div class="absolute left-2 top-2 flex min-w-0 items-center gap-1 rounded bg-background/80 px-2 py-1 font-mono text-[10px] text-primary backdrop-blur-md">
          <span class="material-symbols-outlined text-[12px]">terminal</span> LEVEL: {{ level.toUpperCase() }}
        </div>
      </div>

      <div class="flex w-full min-w-0 flex-1 flex-col p-md">
        <div class="mb-sm flex w-full min-w-0 flex-wrap gap-xs">
          <span class="course-category-tag flex min-w-0 items-center gap-1 rounded border px-xs py-0.5 font-mono text-[10px]" :style="categoryTagStyle()">
            <span class="material-symbols-outlined shrink-0 text-[12px]">code</span>
            <span class="min-w-0 truncate">{{ techTag }}</span>
          </span>
        </div>

        <h3 class="mb-xs w-full min-w-0 font-display text-[18px] font-semibold leading-tight text-on-surface">{{ title }}</h3>
        <p class="mb-md w-full text-body-sm text-on-surface-variant">by {{ instructor }}</p>

        <div class="mt-auto flex w-full min-w-0 flex-col gap-sm">
          <div class="flex w-full min-w-0 items-center justify-between gap-sm">
            <div class="flex min-w-0 items-center gap-1 text-tertiary">
              <span class="material-symbols-outlined filled-star text-[16px]">star</span>
              <span class="font-mono text-label-mono">{{ rating.toFixed(1) }}</span>
            </div>
            <span class="shrink-0 font-display text-[20px] font-semibold text-primary">{{ formatCurrency(price) }}</span>
          </div>

          <button class="w-full cursor-pointer rounded bg-primary-container py-2 font-display text-button-text text-on-primary-container opacity-0 transition-opacity group-hover:opacity-100 group-focus-within:opacity-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-60" type="button" :disabled="cartStore.loading" @click.stop="addToCart">
            {{ cartStore.loading ? 'Adding...' : 'Add to Cart' }}
          </button>

          <p
            v-if="cartFeedback"
            class="w-full text-body-sm"
            :class="cartFeedbackType === 'error' ? 'text-error' : 'text-[var(--accent-success)]'"
          >
            {{ cartFeedback }}
          </p>
        </div>
      </div>
    </article>

    <article class="course-card-preview pointer-events-none absolute left-1/2 top-0 z-20 hidden w-full min-w-0 -translate-x-1/2 rounded-lg border border-surface-variant bg-surface font-body shadow-[0_20px_50px_rgba(0,0,0,0.55)]">
      <div class="relative aspect-video w-full overflow-hidden rounded-t-lg">
        <img v-if="thumbnail" :alt="`${title} preview`" class="h-full w-full object-cover" :src="thumbnail" />
        <div v-else class="flex h-full w-full items-center justify-center bg-surface-container-lowest text-primary">
          <span class="material-symbols-outlined text-5xl">school</span>
        </div>
        <div class="absolute left-2 top-2 flex min-w-0 items-center gap-1 rounded bg-background/90 px-2 py-1 font-mono text-[10px] text-primary">
          <span class="material-symbols-outlined text-[12px]">terminal</span> LEVEL: {{ level.toUpperCase() }}
        </div>
      </div>

      <div class="flex w-full min-w-0 flex-col p-md">
        <div class="flex w-full min-w-0 flex-wrap items-center gap-2">
          <span class="course-category-tag rounded border px-2 py-1 font-mono text-[11px]" :style="categoryTagStyle()">
            {{ techTag }}
          </span>
          <span class="min-w-0 text-body-sm text-on-surface-variant">Đã cập nhật {{ updatedAt }}</span>
        </div>

        <h3 class="mt-sm w-full min-w-0 font-display text-[20px] font-semibold leading-7 text-on-surface">
          {{ title }}
        </h3>

        <p class="mt-xs w-full text-body-sm text-on-surface-variant">Guided by {{ instructor }}</p>

        <div class="mt-sm flex w-full min-w-0 items-center justify-between gap-sm">
          <div class="flex min-w-0 items-center gap-1 text-tertiary">
            <span class="material-symbols-outlined filled-star text-[16px]">star</span>
            <span class="font-mono text-label-mono">{{ rating.toFixed(1) }}</span>
          </div>
          <span class="shrink-0 font-display text-[20px] font-semibold text-primary">{{ formatCurrency(price) }}</span>
        </div>

        <p class="mt-md w-full text-body-sm leading-6 text-on-surface-variant">
          {{ description }}
        </p>

        <ul class="mt-md w-full space-y-3">
          <li
            v-for="highlight in highlights.slice(0, 3)"
            :key="highlight"
            class="flex w-full items-start gap-2 text-body-sm leading-5 text-on-surface"
          >
            <span class="mt-0.5 shrink-0 font-bold text-[var(--accent-success)]">✓</span>
            <span class="min-w-0 flex-1">{{ highlight }}</span>
          </li>
        </ul>

        <button
          class="mt-lg w-full cursor-pointer rounded-lg bg-primary px-md py-3 font-display text-button-text font-semibold text-on-primary transition-opacity hover:opacity-90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-60"
          type="button"
          :disabled="cartStore.loading"
          @click.stop="addToCart"
        >
          {{ cartStore.loading ? 'Adding...' : 'Add to Cart' }}
        </button>

        <p
          v-if="cartFeedback"
          class="mt-sm w-full text-body-sm"
          :class="cartFeedbackType === 'error' ? 'text-error' : 'text-[var(--accent-success)]'"
        >
          {{ cartFeedback }}
        </p>
      </div>
    </article>
  </div>
</template>

<style scoped>
.course-card-base {
  transition:
    opacity 200ms cubic-bezier(0.16, 1, 0.3, 1),
    border-color 200ms cubic-bezier(0.16, 1, 0.3, 1),
    box-shadow 200ms cubic-bezier(0.16, 1, 0.3, 1);
}

.course-card-base:hover {
  transform: none;
}

.course-category-tag {
  color: var(--category-accent);
  border-color: color-mix(in srgb, var(--category-accent) 45%, var(--border-subtle));
  background-color: color-mix(in srgb, var(--category-accent) 12%, transparent);
}

.course-card-preview {
  opacity: 0;
  visibility: hidden;
  transform: translateX(-50%) translateY(8px);
  transition:
    opacity 200ms cubic-bezier(0.16, 1, 0.3, 1),
    visibility 200ms cubic-bezier(0.16, 1, 0.3, 1),
    transform 200ms cubic-bezier(0.16, 1, 0.3, 1);
}

@media (hover: hover) and (pointer: fine) {
  .course-card-preview {
    display: block;
  }

  .course-card-wrapper:hover,
  .course-card-wrapper:focus-within {
    z-index: 30;
  }

  .course-card-wrapper:hover .course-card-base,
  .course-card-wrapper:focus-within .course-card-base {
    opacity: 0;
  }

  .course-card-wrapper:hover .course-card-preview,
  .course-card-wrapper:focus-within .course-card-preview {
    pointer-events: auto;
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(-6px);
  }
}

@media (prefers-reduced-motion: reduce) {
  .course-card-base,
  .course-card-preview {
    transform: none;
    transition: none;
  }
}
</style>
