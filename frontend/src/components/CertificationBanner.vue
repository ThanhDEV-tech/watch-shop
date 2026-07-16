<script setup>
import { RouterLink } from 'vue-router'

const featuredCertifications = [
  { icon: 'cloud_done', name: 'AWS' },
  { icon: 'shield', name: 'CompTIA' },
  { icon: 'verified', name: 'PMI' },
]
</script>

<template>
  <section class="py-xl bg-surface-container-low border-y border-surface-variant font-body">
    <div class="max-w-container-max mx-auto px-margin-mobile md:px-gutter">
      <div class="flex w-full min-w-0 flex-col md:flex-row items-center justify-between gap-lg">
        <div class="w-full min-w-0 max-w-[36rem] flex-1 text-center md:text-left">
          <h2 class="font-display text-headline-md text-on-background mb-md font-semibold">Get Certified and Advance Your Career</h2>
          <p class="text-on-surface-variant text-body-lg mb-lg">Unlock professional opportunities with industry-recognized certifications. Our paths prepare you for the toughest exams.</p>
          <RouterLink :to="{ name: 'certifications' }" class="inline-flex w-auto min-w-max items-center justify-center whitespace-nowrap rounded-lg bg-primary px-lg py-3 font-display text-button-text font-bold text-on-primary transition-all hover:opacity-90">Khám phá Chứng chỉ</RouterLink>
        </div>
        <div class="grid w-full min-w-0 grid-cols-3 gap-md shrink-0 md:w-auto">
          <div v-for="certification in featuredCertifications" :key="certification.name" class="group flex w-full min-w-0 flex-col items-center gap-2 rounded-xl border border-surface-variant bg-surface-container-highest p-4 transition-all hover:border-primary">
            <div class="flex h-16 w-16 min-w-16 shrink-0 items-center justify-center rounded-lg bg-background">
              <span class="material-symbols-outlined block shrink-0 whitespace-nowrap text-3xl leading-none text-primary" aria-hidden="true">{{ certification.icon }}</span>
            </div>
            <span class="w-full min-w-0 text-center font-mono text-[10px] text-on-surface">{{ certification.name }}</span>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
