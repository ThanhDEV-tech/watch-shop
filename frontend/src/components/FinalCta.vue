<script setup>
import { RouterLink } from 'vue-router'
</script>

<template>
  <section class="w-full overflow-hidden border-y border-surface-variant bg-surface-container-low py-xl font-body">
    <div class="mx-auto w-full min-w-0 max-w-container-max px-margin-mobile md:px-gutter">
      <div class="relative flex w-full min-w-0 flex-col items-center overflow-hidden rounded-2xl border border-surface-variant bg-surface px-md py-xl text-center">
        <div class="hero-glow"></div>
        <div class="relative z-10 w-full min-w-0 max-w-3xl">
          <p class="w-full font-mono text-label-mono uppercase tracking-widest text-primary">Start building</p>
          <h2 class="mt-sm w-full font-display text-headline-md font-semibold text-on-background md:text-[40px]">
            Sẵn sàng bắt đầu hành trình học tập?
          </h2>
          <p class="mx-auto mt-md w-full max-w-2xl text-body-lg text-on-surface-variant">
            Chọn một kỹ năng, theo học theo lộ trình rõ ràng, thanh toán an toàn và bắt đầu luyện tập cùng trợ lý AI khi cần.
          </p>
          <div class="mt-lg flex w-full min-w-0 flex-col items-center justify-center gap-sm sm:flex-row">
            <RouterLink
              to="/register"
              class="inline-flex w-full items-center justify-center rounded-lg bg-primary px-lg py-3 font-display text-button-text font-bold text-on-primary transition-all hover:opacity-90 sm:w-auto"
            >
              Đăng ký miễn phí
            </RouterLink>
            <a
              href="#courses"
              class="inline-flex w-full items-center justify-center rounded-lg border border-surface-variant bg-background px-lg py-3 font-display text-button-text font-bold text-on-surface transition-all hover:border-primary hover:text-primary sm:w-auto"
            >
              Khám phá khóa học
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
