<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { getCategories } from '../api/axios'

const categories = ref([])
const isLoading = ref(true)
const errorMessage = ref('')

const iconMap = {
  backend: 'dns',
  'web-development': 'code',
  'ui-ux-design': 'design_services',
  'devops-cloud': 'cloud_done',
  'mobile-development': 'phone_iphone',
  'data-ai': 'psychology',
  'graphic-design': 'brush',
}

const featuredCategories = computed(() => categories.value.slice(0, 7))

const resolveIcon = (category) => category.icon || iconMap[category.slug] || 'terminal'

const categoryStyle = (category) => ({
  '--category-accent': category.accent_color || '#FF6B4A',
})

onMounted(async () => {
  try {
    const response = await getCategories()
    categories.value = response.data.data
  } catch (error) {
    errorMessage.value = error.response?.data?.message ?? 'Không thể tải danh mục nổi bật.'
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <section class="w-full bg-background py-xl font-body">
    <div class="mx-auto w-full min-w-0 max-w-container-max px-margin-mobile md:px-gutter">
      <div class="mb-lg w-full min-w-0">
        <p class="mb-xs w-full font-mono text-label-mono uppercase tracking-widest text-primary">Featured categories</p>
        <h2 class="w-full font-display text-headline-md font-semibold text-on-background">Khám phá theo kỹ năng bạn muốn nâng cấp</h2>
      </div>

      <div v-if="isLoading" class="grid w-full grid-cols-1 gap-md sm:grid-cols-2 lg:grid-cols-4">
        <div v-for="index in 7" :key="index" class="h-40 animate-pulse rounded-xl border border-surface-variant bg-surface"></div>
      </div>

      <p v-else-if="errorMessage" class="w-full py-lg text-center text-error">{{ errorMessage }}</p>

      <div v-else-if="featuredCategories.length" class="grid w-full min-w-0 grid-cols-1 gap-md sm:grid-cols-2 lg:grid-cols-4">
        <RouterLink
          v-for="category in featuredCategories"
          :key="category.id"
          :to="`/category/${category.slug}`"
          class="featured-category-card course-card-element flex w-full min-w-0 flex-col rounded-xl border bg-surface p-md"
          :style="categoryStyle(category)"
        >
          <div class="featured-category-icon mb-md flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border bg-background">
            <span class="material-symbols-outlined text-[26px]" aria-hidden="true">{{ resolveIcon(category) }}</span>
          </div>

          <div class="w-full min-w-0">
            <h3 class="w-full font-display text-[20px] font-semibold text-on-surface">{{ category.name }}</h3>
            <p class="mt-2 line-clamp-2 w-full text-body-sm text-on-surface-variant">
              {{ category.description || 'Lộ trình học thực chiến cho lập trình viên muốn tiến nhanh hơn.' }}
            </p>
          </div>

          <p class="featured-category-meta mt-md w-full font-mono text-label-mono">
            {{ Number(category.courses_count ?? 0).toLocaleString('vi-VN') }} khóa học
          </p>
        </RouterLink>
      </div>

      <p v-else class="w-full py-lg text-center text-on-surface-variant">Chưa có danh mục nào để hiển thị.</p>
    </div>
  </section>
</template>

<style scoped>
.featured-category-card {
  border-color: color-mix(in srgb, var(--category-accent) 30%, var(--border-subtle));
  perspective: 700px;
}

.featured-category-card:hover {
  border-color: color-mix(in srgb, var(--category-accent) 62%, var(--border-subtle));
}

.featured-category-icon {
  color: var(--category-accent);
  border-color: color-mix(in srgb, var(--category-accent) 45%, var(--border-subtle));
  transform: perspective(600px) translateZ(0);
  transition:
    transform 220ms cubic-bezier(0.16, 1, 0.3, 1),
    box-shadow 220ms cubic-bezier(0.16, 1, 0.3, 1);
}

.featured-category-card:hover .featured-category-icon {
  transform: perspective(600px) translateY(-2px) translateZ(18px) scale(1.04);
  box-shadow: 0 14px 28px color-mix(in srgb, var(--category-accent) 20%, transparent);
}

.featured-category-meta {
  color: var(--category-accent);
}

@media (prefers-reduced-motion: reduce) {
  .featured-category-icon,
  .featured-category-card:hover .featured-category-icon {
    transform: none;
    transition: none;
  }
}
</style>
