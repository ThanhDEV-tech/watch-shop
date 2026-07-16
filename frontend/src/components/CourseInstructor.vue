<script setup>
defineProps({
  instructor: { type: Object, required: true },
})
</script>

<template>
  <section class="glass-card w-full min-w-0 p-md rounded-xl">
    <h2 class="w-full font-display text-headline-sm mb-lg text-on-surface">Your Instructor</h2>
    <div class="flex w-full min-w-0 flex-col md:flex-row gap-lg items-center md:items-start">
      <div class="relative min-w-0 shrink-0">
        <div class="w-32 h-32 rounded-xl overflow-hidden border-2 border-primary-container">
          <img v-if="instructor.avatar" class="w-full h-full object-cover" :src="instructor.avatar" :alt="instructor.name" />
          <div v-else class="flex h-full w-full items-center justify-center bg-surface-container-lowest text-primary">
            <span class="material-symbols-outlined text-5xl">person</span>
          </div>
        </div>
      </div>
      <div class="w-full min-w-0 flex-1 space-y-base text-center md:text-left">
        <h3 class="font-display text-headline-sm text-on-surface">{{ instructor.name }}</h3>
        <p class="font-mono text-label-mono text-primary mb-sm uppercase tracking-wider">{{ instructor.role }}</p>
        <p class="w-full text-body-md leading-7 text-on-surface-variant">{{ instructor.bio || 'Giảng viên đang cập nhật phần giới thiệu cá nhân.' }}</p>
        <div class="grid w-full min-w-0 grid-cols-1 gap-sm pt-sm sm:grid-cols-3">
          <div class="w-full min-w-0 rounded-lg border border-surface-variant bg-surface-container-low p-sm">
            <p class="w-full font-display text-headline-sm font-semibold text-on-surface">{{ instructor.totalCourses ?? 0 }}</p>
            <p class="w-full font-mono text-[11px] uppercase tracking-widest text-on-surface-variant">Khóa học</p>
          </div>
          <div class="w-full min-w-0 rounded-lg border border-surface-variant bg-surface-container-low p-sm">
            <p class="w-full font-display text-headline-sm font-semibold text-on-surface">{{ instructor.totalStudents ?? 0 }}</p>
            <p class="w-full font-mono text-[11px] uppercase tracking-widest text-on-surface-variant">Học viên</p>
          </div>
          <div class="w-full min-w-0 rounded-lg border border-surface-variant bg-surface-container-low p-sm">
            <p class="w-full font-display text-headline-sm font-semibold text-on-surface">{{ Number(instructor.ratingAvg ?? 0).toFixed(1) }}</p>
            <p class="w-full font-mono text-[11px] uppercase tracking-widest text-on-surface-variant">Rating</p>
          </div>
        </div>
        <div class="flex flex-wrap gap-xs justify-center md:justify-start pt-base">
          <span v-for="skill in instructor.skills" :key="skill" class="bg-surface-container-highest px-3 py-1 rounded-full font-mono text-label-mono text-on-surface-variant border border-surface-variant">{{ skill }}</span>
        </div>
      </div>
    </div>
  </section>
</template>
