<script setup>
const steps = [
  ['01', 'Register', 'Create your developer account in seconds.'],
  ['02', 'Choose Course', 'Select from expert-led engineering paths.'],
  ['03', 'Learn', 'Deep dive into code with terminal-grade videos.'],
  ['04', 'Get Certified', 'Validate your mastery with final assessments.'],
]
</script>

<template>
  <section class="bg-surface-container-lowest py-xl border-b border-surface-variant font-body">
    <div class="max-w-container-max mx-auto px-margin-mobile md:px-gutter">
      <h2 class="font-display text-headline-md text-on-background mb-lg text-center font-semibold">How EduMarket Works</h2>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-lg">
        <article v-for="[number, title, description] in steps" :key="number" class="relative flex min-h-[160px] w-full min-w-0 flex-col justify-end overflow-hidden rounded-xl border border-surface-variant bg-surface-container p-md transition-colors group hover:border-primary">
          <span class="step-number-bg">{{ number }}</span>
          <div class="relative z-10 w-full min-w-0">
            <h3 class="font-display text-[20px] text-primary mb-1 font-semibold">{{ title }}</h3>
            <p class="text-body-sm text-on-surface-variant">{{ description }}</p>
          </div>
        </article>
      </div>
    </div>
  </section>
</template>
