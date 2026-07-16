<script setup>
const testimonials = [
  { quote: 'The Laravel course changed how I architect my apps. No fluff, just pure high-level patterns.', initials: 'JD', name: 'John Doe', role: 'Senior PHP Engineer', tag: 'LARAVEL' },
  { quote: 'EduMarket is my go-to for staying ahead of the curve. The Docker course is essential.', initials: 'AL', name: 'Alex Lin', role: 'DevOps Architect', tag: 'DOCKER' },
  { quote: 'Most UI courses are for designers. This one is actually for developers. Game changer.', initials: 'MW', name: 'Maria West', role: 'Frontend Lead', tag: 'UI/UX' },
  { quote: 'Finally a marketplace that treats coding like engineering, not a hobby. Super high quality.', initials: 'BC', name: 'Ben Chen', role: 'Fullstack Dev', tag: 'REACT' },
]
</script>

<template>
  <section class="py-xl bg-background font-body">
    <div class="max-w-container-max mx-auto px-margin-mobile md:px-gutter">
      <h2 class="font-display text-headline-md text-on-background mb-xl text-center font-semibold">What developers are saying</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-md">
        <article v-for="review in testimonials" :key="review.name" class="flex w-full min-w-0 flex-col rounded-xl border border-surface-variant bg-surface-container-low p-md">
          <span class="material-symbols-outlined text-primary mb-sm">format_quote</span>
          <p class="text-body-md text-on-surface mb-lg flex-1 italic">“{{ review.quote }}”</p>
          <div class="flex w-full min-w-0 items-center gap-sm">
            <div class="w-10 min-w-0 h-10 shrink-0 rounded-full bg-surface-container-highest flex items-center justify-center text-primary font-bold">{{ review.initials }}</div>
            <div class="min-w-0 flex-1">
              <p class="font-bold text-on-surface text-body-sm leading-none">{{ review.name }}</p>
              <p class="text-on-surface-variant text-xs">{{ review.role }}</p>
            </div>
          </div>
          <div class="mt-sm w-full min-w-0 pt-sm border-t border-surface-variant/50">
            <span class="bg-surface-variant text-on-surface-variant text-[10px] px-2 py-0.5 rounded font-mono">{{ review.tag }}</span>
          </div>
        </article>
      </div>
    </div>
  </section>
</template>
