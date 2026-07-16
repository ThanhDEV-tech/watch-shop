<script setup>
import { computed, onMounted, ref } from 'vue'
import { getFeaturedInstructors } from '../services/api'

const instructors = ref([])
const isLoading = ref(true)
const errorMessage = ref('')

const initials = (name) => String(name ?? 'EM')
  .split(' ')
  .filter(Boolean)
  .slice(0, 2)
  .map((part) => part[0])
  .join('')
  .toUpperCase()

const hasInstructors = computed(() => instructors.value.length > 0)

onMounted(async () => {
  try {
    const response = await getFeaturedInstructors()
    instructors.value = response.data.data
  } catch (error) {
    errorMessage.value = error.response?.data?.message ?? 'Không thể tải danh sách giảng viên.'
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <section class="w-full bg-surface-container-low py-xl font-body">
    <div class="mx-auto w-full min-w-0 max-w-container-max px-margin-mobile md:px-gutter">
      <div class="mb-lg flex w-full min-w-0 flex-col items-center text-center">
        <p class="w-full font-mono text-label-mono uppercase tracking-widest text-primary">Instructor spotlight</p>
        <h2 class="mt-xs w-full font-display text-headline-md font-semibold text-on-background">Gặp gỡ giảng viên hàng đầu</h2>
        <p class="mt-sm w-full max-w-2xl text-body-md text-on-surface-variant">
          Những người đang xây dựng, vận hành và giảng dạy các hệ thống phần mềm thực tế.
        </p>
      </div>

      <div v-if="isLoading" class="grid w-full grid-cols-1 gap-md md:grid-cols-2 lg:grid-cols-4">
        <div v-for="index in 4" :key="index" class="h-64 animate-pulse rounded-xl border border-surface-variant bg-surface"></div>
      </div>

      <p v-else-if="errorMessage" class="w-full py-lg text-center text-error">{{ errorMessage }}</p>

      <div v-else-if="hasInstructors" class="grid w-full min-w-0 grid-cols-1 gap-md md:grid-cols-2 lg:grid-cols-4">
        <article
          v-for="instructor in instructors"
          :key="instructor.id"
          class="course-card-element flex w-full min-w-0 flex-col items-center rounded-xl border border-surface-variant bg-surface p-md text-center"
        >
          <img
            v-if="instructor.avatar_url"
            :src="instructor.avatar_url"
            :alt="instructor.name"
            class="instructor-avatar h-20 w-20 shrink-0 rounded-full border border-surface-variant object-cover"
          />
          <div v-else class="instructor-avatar flex h-20 w-20 shrink-0 items-center justify-center rounded-full border border-surface-variant bg-background font-display text-xl font-bold text-primary">
            {{ initials(instructor.name) }}
          </div>

          <div class="mt-md w-full min-w-0">
            <h3 class="w-full truncate font-display text-[20px] font-semibold text-on-surface">{{ instructor.name }}</h3>
            <p class="mt-1 w-full truncate font-mono text-label-mono uppercase text-primary">{{ instructor.specialty }}</p>
          </div>

          <div class="mt-md grid w-full min-w-0 grid-cols-3 gap-sm border-t border-surface-variant pt-md">
            <div class="w-full min-w-0">
              <p class="w-full font-display text-lg font-semibold text-on-surface">{{ instructor.courses_count }}</p>
              <p class="w-full text-[11px] text-on-surface-variant">khóa học</p>
            </div>
            <div class="w-full min-w-0">
              <p class="w-full font-display text-lg font-semibold text-on-surface">{{ Number(instructor.total_students ?? 0).toLocaleString('vi-VN') }}</p>
              <p class="w-full text-[11px] text-on-surface-variant">học viên</p>
            </div>
            <div class="w-full min-w-0">
              <p class="w-full font-display text-lg font-semibold text-tertiary">{{ Number(instructor.rating_avg ?? 0).toFixed(1) }}</p>
              <p class="w-full text-[11px] text-on-surface-variant">rating</p>
            </div>
          </div>
        </article>
      </div>

      <p v-else class="w-full py-lg text-center text-on-surface-variant">Chưa có giảng viên nổi bật để hiển thị.</p>
    </div>
  </section>
</template>

<style scoped>
.instructor-avatar {
  transform: perspective(600px) rotateX(0deg) rotateY(0deg);
  transition:
    transform 240ms cubic-bezier(0.16, 1, 0.3, 1),
    box-shadow 240ms cubic-bezier(0.16, 1, 0.3, 1);
}

article:hover .instructor-avatar {
  transform: perspective(600px) rotateX(7deg) rotateY(-7deg) translateZ(10px);
  box-shadow: 0 16px 30px rgba(0, 0, 0, 0.35);
}

@media (prefers-reduced-motion: reduce) {
  .instructor-avatar,
  article:hover .instructor-avatar {
    transform: none;
    transition: none;
  }
}
</style>
