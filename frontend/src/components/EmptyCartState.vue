<template>
  <section class="flex w-full min-w-0 flex-col items-center justify-center py-xl text-center">
    <div class="w-24 min-w-0 h-24 bg-surface-container rounded-full flex items-center justify-center mb-md border border-outline-variant">
      <span class="material-symbols-outlined text-4xl text-on-surface-variant">shopping_basket</span>
    </div>
    <h2 class="font-display text-headline-sm mb-base">Your cart is empty</h2>
    <p class="w-full max-w-[28rem] text-on-surface-variant mb-lg">Looks like you haven't added any courses to your cart yet. Explore our top-rated courses to start your learning journey.</p>
    <RouterLink to="/" class="bg-primary text-on-primary px-lg py-3 rounded-lg font-display text-button-text hover:opacity-90 active:scale-95 transition-all">Browse Courses</RouterLink>
  </section>
</template>
