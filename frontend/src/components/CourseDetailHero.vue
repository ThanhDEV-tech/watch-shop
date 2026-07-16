<script setup>
defineProps({
  course: { type: Object, required: true },
})
</script>

<template>
  <section class="w-full min-w-0 space-y-sm">
    <div class="flex w-full min-w-0 items-center gap-xs text-primary font-mono text-label-mono">
      <span class="material-symbols-outlined text-[18px]">terminal</span>
      <span>{{ course.level }}</span>
    </div>
    <h1 class="w-full font-display text-display-lg text-on-surface font-bold">{{ course.title }}</h1>
    <p class="w-full max-w-[42rem] text-body-lg text-on-surface-variant">{{ course.description }}</p>
    <div class="flex w-full flex-wrap items-center gap-md pt-base">
      <div class="flex min-w-0 items-center gap-1">
        <span class="material-symbols-outlined text-tertiary filled-star">star</span>
        <span class="font-display text-[20px] text-on-surface">{{ course.rating }}</span>
      </div>
      <span class="text-on-surface-variant text-body-sm">({{ course.students }} Students enrolled)</span>
      <span class="text-on-surface-variant text-body-sm">•</span>
      <span class="text-on-surface-variant text-body-sm">Created by <span class="text-primary font-semibold">{{ course.instructor.name }}</span></span>
    </div>
  </section>
</template>
