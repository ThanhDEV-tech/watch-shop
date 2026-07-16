<script setup>
import { ref } from 'vue'

const props = defineProps({
  chapters: { type: Array, required: true },
})

const emit = defineEmits(['lesson-click'])
const openChapter = ref(0)

const handleLessonClick = (lesson) => {
  if (lesson.canWatch) {
    emit('lesson-click', lesson)
  }
}
</script>

<template>
  <section class="w-full min-w-0 space-y-md">
    <h2 class="font-display text-headline-sm text-on-surface">Course Curriculum</h2>
    <div class="w-full space-y-base">
      <article v-for="(chapter, index) in chapters" :key="chapter.title" class="glass-card w-full min-w-0 rounded-lg overflow-hidden transition-all duration-300">
        <button class="w-full flex cursor-pointer items-center justify-between gap-md p-md hover:bg-surface-container transition-colors text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background" type="button" @click="openChapter = openChapter === index ? -1 : index">
          <div class="flex min-w-0 items-center gap-md">
            <span class="shrink-0 font-display font-bold text-headline-sm text-on-surface-variant select-none">{{ String(index + 1).padStart(2, '0') }}</span>
            <div class="min-w-0">
              <h3 class="font-display text-[18px] text-on-surface">{{ chapter.title }}</h3>
              <p class="text-body-sm text-on-surface-variant">{{ chapter.summary }}</p>
            </div>
          </div>
          <span class="material-symbols-outlined shrink-0 text-primary transition-transform" :class="openChapter === index ? 'rotate-180' : ''">expand_more</span>
        </button>
        <div v-show="openChapter === index" class="p-md pt-0 space-y-sm">
          <button
            v-for="lesson in chapter.lessons"
            :key="lesson.id ?? lesson.title"
            type="button"
            class="flex w-full min-w-0 items-center justify-between gap-md rounded-lg p-sm text-left transition-colors hover:bg-surface-container-low focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background"
            :class="lesson.canWatch ? 'cursor-pointer' : 'cursor-not-allowed opacity-80'"
            :title="lesson.canWatch ? '' : 'Mua khóa học để xem'"
            @click="handleLessonClick(lesson)"
          >
            <div class="flex min-w-0 items-center gap-sm">
              <span class="material-symbols-outlined shrink-0 text-on-surface-variant">
                {{ lesson.canWatch ? (lesson.isFreePreview ? 'play_circle' : 'play_circle') : 'lock' }}
              </span>
              <span class="min-w-0 text-body-md text-on-surface-variant">{{ lesson.title }}</span>
            </div>
            <span class="shrink-0 font-mono text-label-mono text-on-surface-variant">{{ lesson.duration }}</span>
          </button>
        </div>
      </article>
    </div>
  </section>
</template>
