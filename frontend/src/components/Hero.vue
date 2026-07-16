<script setup>
import { ref } from 'vue'

const searchQuery = ref('')
</script>

<template>
  <section class="relative overflow-hidden pt-xl pb-24 md:pt-32 md:pb-40 px-margin-mobile md:px-gutter max-w-container-max mx-auto font-body">
    <div class="hero-glow"></div>
    <div class="absolute top-0 right-0 pointer-events-none select-none bracket-motif font-mono text-[240px] leading-none">{ }</div>
    <div class="relative z-10 max-w-3xl">
      <h1 class="font-display text-display-lg-mobile md:text-display-lg font-bold text-on-background mb-md">
        Master the stacks that <br /><span class="text-primary">define the future.</span>
      </h1>
      <p class="text-on-surface-variant text-body-lg mb-lg max-w-2xl">
        The terminal-grade marketplace for developers. Professional courses in modern frameworks, clean architecture, and advanced engineering practices.
      </p>
      <div class="lg:hidden mb-lg relative">
        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
        <input
          v-model="searchQuery"
          class="w-full bg-surface-container-low border border-surface-variant rounded-lg py-4 pl-12 pr-4 focus:outline-none focus:border-primary-container focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background text-body-md text-on-surface"
          placeholder="Find your next course..."
          type="text"
        />
      </div>
      <div class="flex w-full min-w-0 flex-wrap items-center gap-sm mt-md">
        <span class="font-mono text-label-mono text-on-surface-variant">Trending:</span>
        <a v-for="tag in ['Laravel', 'Vue 3', 'Tailwind CSS', 'Rust', 'Docker']" :key="tag" class="cursor-pointer font-mono text-label-mono bg-surface-container-high border border-surface-variant px-sm py-1 rounded-lg hover:border-primary-container hover:text-primary transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background" href="#">
          {{ tag }}
        </a>
      </div>
    </div>
  </section>
</template>
