<template>
  <main class="min-h-[60vh] w-full min-w-0 bg-background px-margin-mobile py-20 text-on-surface md:px-gutter">
    <div class="mx-auto flex w-full max-w-3xl min-w-0 flex-col items-start">
      <p class="w-full text-xs font-semibold uppercase tracking-[0.2em] text-primary/60">
        Watchora
      </p>
      <h1 class="mt-4 w-full font-display text-5xl font-semibold leading-none text-primary md:text-6xl">
        Chi tiết sản phẩm đang được hoàn thiện.
      </h1>
      <p class="mt-5 w-full text-base leading-7 text-on-surface-variant">
        Route này đã sẵn sàng tại /products/{{ $route.params.slug }} để bước tiếp theo nối trang chi tiết, chọn variant và thêm vào giỏ hàng.
      </p>
      <RouterLink
        to="/products"
        class="mt-8 inline-flex min-h-12 items-center justify-center rounded-[var(--radius-watch-md)] bg-primary px-6 text-sm font-semibold uppercase tracking-[0.14em] text-on-primary transition-colors hover:bg-[var(--accent-primary-hover)]"
      >
        Quay lại catalog
      </RouterLink>
    </div>
  </main>
</template>
