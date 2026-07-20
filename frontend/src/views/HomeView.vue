<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { gsap } from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'
import ProductShowcaseGrid from '../components/ProductShowcaseGrid.vue'
import { getBrands, getCollections, getProducts } from '../api/axios'

gsap.registerPlugin(ScrollTrigger)

const featuredProducts = ref([])
const partnerBrands = ref([])
const collectionHighlights = ref([])
const isLoadingProducts = ref(false)
const homeRootRef = ref(null)
const heroTiltRef = ref(null)
const heroImagePanelRef = ref(null)
const campaignBannerRef = ref(null)
const campaignImageRef = ref(null)
let heroTiltMatchMedia
let scrollMatchMedia
let removeHeroTiltListeners = () => {}

const faqs = [
  {
    question: 'Watchora có hỗ trợ bảo hành không?',
    answer: 'Có. Mỗi sản phẩm có thời hạn bảo hành riêng theo thông tin trên trang chi tiết, thường từ 12 tháng cho lỗi kỹ thuật của máy.',
  },
  {
    question: 'Tôi có thể chọn đồng hồ làm quà tặng như thế nào?',
    answer: 'Bạn có thể lọc theo nam, nữ, thương hiệu và bộ sưu tập. Với quà tặng, Watchora ưu tiên các mẫu dây thép hoặc da trung tính, dễ đeo hằng ngày.',
  },
  {
    question: 'Phí vận chuyển được tính ra sao?',
    answer: 'Phí giao hàng phụ thuộc khu vực nhận hàng. Hệ thống sẽ hiển thị rõ phí trước khi bạn thanh toán.',
  },
  {
    question: 'Nếu sản phẩm tạm hết hàng thì sao?',
    answer: 'Card sản phẩm sẽ hiển thị trạng thái Hết hàng khi toàn bộ phiên bản/SKU không còn tồn kho.',
  },
]

const uspItems = [
  {
    icon: 'workspace_premium',
    title: 'Bảo hành rõ ràng',
    copy: 'Thông tin bảo hành theo từng sản phẩm, lưu lại cùng đơn hàng để dễ đối chiếu.',
  },
  {
    icon: 'verified',
    title: 'Nguồn hàng chọn lọc',
    copy: 'Watchora ưu tiên các mẫu có thiết kế bền gu, dễ phối và phù hợp nhiều dịp.',
  },
  {
    icon: 'sync_alt',
    title: 'Đổi trả 7 ngày',
    copy: 'Hỗ trợ đổi trả khi sản phẩm chưa qua sử dụng và còn đầy đủ phụ kiện.',
  },
  {
    icon: 'local_shipping',
    title: 'Giao hàng nhanh',
    copy: 'Khu vực nội thành được ưu tiên xử lý nhanh, phí giao hàng hiển thị trước thanh toán.',
  },
]

const visibleBrands = computed(() => partnerBrands.value.slice(0, 6))

const fetchHomeData = async () => {
  isLoadingProducts.value = true

  try {
    const [productsResponse, brandsResponse, collectionsResponse] = await Promise.all([
      getProducts({ per_page: 4 }),
      getBrands(),
      getCollections(),
    ])

    featuredProducts.value = productsResponse.data.data?.items ?? []
    partnerBrands.value = brandsResponse.data.data ?? []
    collectionHighlights.value = (collectionsResponse.data.data ?? [])
      .filter((collection) => (collection.products_count ?? 0) > 0)
      .slice(0, 3)
      .map((collection) => ({
        name: collection.name,
        slug: collection.slug,
        copy: collection.description || 'Khám phá các mẫu đồng hồ được tuyển chọn theo cùng một tinh thần phối đồ.',
        query: { collection: collection.slug },
      }))
  } finally {
    isLoadingProducts.value = false
  }
}

const setupHeroTilt = () => {
  if (!heroTiltRef.value || !heroImagePanelRef.value) return

  heroTiltMatchMedia = gsap.matchMedia()

  heroTiltMatchMedia.add('(prefers-reduced-motion: reduce)', () => {
    gsap.set(heroImagePanelRef.value, { clearProps: 'transform' })
  })

  heroTiltMatchMedia.add('(prefers-reduced-motion: no-preference)', () => {
    const panel = heroImagePanelRef.value
    const area = heroTiltRef.value
    const rotateXTo = gsap.quickTo(panel, 'rotationX', { duration: 0.45, ease: 'power3.out' })
    const rotateYTo = gsap.quickTo(panel, 'rotationY', { duration: 0.45, ease: 'power3.out' })
    const scaleTo = gsap.quickTo(panel, 'scale', { duration: 0.45, ease: 'power3.out' })

    gsap.set(panel, {
      transformPerspective: 1200,
      transformOrigin: '50% 50%',
      transformStyle: 'preserve-3d',
      willChange: 'transform',
    })

    const handlePointerMove = (event) => {
      const bounds = area.getBoundingClientRect()
      const xRatio = (event.clientX - bounds.left) / bounds.width - 0.5
      const yRatio = (event.clientY - bounds.top) / bounds.height - 0.5

      rotateYTo(gsap.utils.clamp(-4, 4, xRatio * 8))
      rotateXTo(gsap.utils.clamp(-4, 4, yRatio * -8))
      scaleTo(1.018)
    }

    const handlePointerLeave = () => {
      rotateXTo(0)
      rotateYTo(0)
      scaleTo(1)
    }

    area.addEventListener('pointermove', handlePointerMove)
    area.addEventListener('pointerleave', handlePointerLeave)

    removeHeroTiltListeners = () => {
      area.removeEventListener('pointermove', handlePointerMove)
      area.removeEventListener('pointerleave', handlePointerLeave)
    }

    return () => {
      removeHeroTiltListeners()
      removeHeroTiltListeners = () => {}
      gsap.set(panel, { clearProps: 'transform,willChange' })
    }
  })
}

const setupScrollEffects = () => {
  if (!homeRootRef.value) return

  scrollMatchMedia = gsap.matchMedia()

  scrollMatchMedia.add('(prefers-reduced-motion: reduce)', () => {
    const scoped = gsap.utils.selector(homeRootRef.value)

    gsap.set([
      campaignImageRef.value,
      ...scoped('.watch-collection-card'),
      ...scoped('.watch-usp-card'),
    ].filter(Boolean), { clearProps: 'all' })
  })

  scrollMatchMedia.add('(prefers-reduced-motion: no-preference)', () => {
    const scoped = gsap.utils.selector(homeRootRef.value)
    const collectionCards = scoped('.watch-collection-card')
    const uspCards = scoped('.watch-usp-card')
    const triggers = []

    if (campaignBannerRef.value && campaignImageRef.value) {
      gsap.set(campaignImageRef.value, { yPercent: -6, scale: 1.1, willChange: 'transform' })

      gsap.to(campaignImageRef.value, {
        yPercent: 6,
        ease: 'none',
        scrollTrigger: {
          trigger: campaignBannerRef.value,
          start: 'top bottom',
          end: 'bottom top',
          scrub: 0.8,
        },
      })
    }

    if (collectionCards.length) {
      gsap.set(collectionCards, { autoAlpha: 0, y: 28, scale: 0.98 })
      triggers.push(...ScrollTrigger.batch(collectionCards, {
        start: 'top 86%',
        once: true,
        batchMax: 3,
        interval: 0.08,
        onEnter: (batch) => {
          gsap.to(batch, {
            autoAlpha: 1,
            y: 0,
            scale: 1,
            duration: 0.55,
            ease: 'power2.out',
            stagger: 0.08,
            overwrite: true,
          })
        },
      }))
    }

    if (uspCards.length) {
      gsap.set(uspCards, { autoAlpha: 0, y: 24 })
      triggers.push(...ScrollTrigger.batch(uspCards, {
        start: 'top 88%',
        once: true,
        batchMax: 4,
        interval: 0.08,
        onEnter: (batch) => {
          gsap.to(batch, {
            autoAlpha: 1,
            y: 0,
            duration: 0.5,
            ease: 'power2.out',
            stagger: 0.07,
            overwrite: true,
          })
        },
      }))
    }

    ScrollTrigger.refresh()

    return () => {
      triggers.forEach((trigger) => trigger.kill())
      gsap.set(campaignImageRef.value, { clearProps: 'transform,willChange' })
    }
  })
}

onMounted(() => {
  fetchHomeData()
  setupHeroTilt()
  setupScrollEffects()
})

onBeforeUnmount(() => {
  removeHeroTiltListeners()
  heroTiltMatchMedia?.revert()
  scrollMatchMedia?.revert()
})
</script>

<template>
  <main ref="homeRootRef" class="w-full min-w-0 bg-background text-on-surface">
    <section class="relative overflow-hidden border-b border-border bg-surface">
      <div class="mx-auto grid min-h-[calc(100vh-5rem)] w-full max-w-container-max gap-10 px-margin-mobile py-16 md:px-gutter lg:grid-cols-[1.05fr_0.95fr] lg:items-center">
        <div class="flex w-full min-w-0 flex-col items-start">
          <p class="watch-accent-text w-full text-xs font-bold uppercase tracking-[0.22em]">
            Watchora
          </p>
          <h1 class="mt-5 w-full font-display text-[clamp(3.5rem,9vw,8rem)] font-semibold leading-[0.86] text-primary">
            Thời gian, được chọn có gu.
          </h1>
          <p class="mt-7 w-full max-w-2xl text-base leading-8 text-on-surface-variant md:text-lg">
            Watchora tuyển chọn đồng hồ thời trang nam và nữ theo tinh thần tối giản, sang trọng và đáng tin cậy. Mỗi thiết kế là một món phụ kiện có giá trị, đủ tinh tế cho bản thân và đủ trang trọng để làm quà tặng.
          </p>
          <div class="mt-9 flex w-full min-w-0 flex-col gap-3 sm:flex-row">
            <RouterLink
              to="/products"
              class="inline-flex min-h-12 items-center justify-center rounded-[var(--radius-watch-md)] bg-primary px-6 text-sm font-semibold uppercase tracking-[0.14em] text-on-primary transition-colors hover:bg-[var(--accent-primary-hover)]"
            >
              Xem sản phẩm
            </RouterLink>
            <RouterLink
              :to="{ name: 'products', query: { gender_target: 'women' } }"
              class="inline-flex min-h-12 items-center justify-center rounded-[var(--radius-watch-md)] border border-border px-6 text-sm font-semibold uppercase tracking-[0.14em] text-primary transition-colors hover:border-primary"
            >
              Gợi ý quà tặng
            </RouterLink>
          </div>
        </div>

        <div ref="heroTiltRef" class="relative w-full min-w-0 [perspective:1200px]">
          <div ref="heroImagePanelRef" class="aspect-[4/5] w-full overflow-hidden rounded-[var(--radius-watch-lg)] bg-[var(--watch-color-ink-900)]">
            <img
              src="https://images.unsplash.com/photo-1547996160-81dfa63595aa?auto=format&fit=crop&w=1100&q=85"
              alt="Đồng hồ thời trang cao cấp trên nền tối giản"
              class="h-full w-full object-cover opacity-90"
            />
          </div>
          <div class="absolute bottom-5 left-5 right-5 flex min-w-0 items-center justify-between gap-4 rounded-[var(--radius-watch-md)] border border-white/20 bg-white/86 px-5 py-4 backdrop-blur">
            <div class="w-full min-w-0">
              <p class="watch-accent-text w-full text-xs font-bold uppercase tracking-[0.16em]">
                New edit
              </p>
              <p class="w-full truncate font-display text-2xl font-semibold text-primary">
                Quiet Luxury Watches
              </p>
            </div>
            <RouterLink to="/products" class="watch-accent-strong shrink-0 text-sm font-bold">
              Khám phá
            </RouterLink>
          </div>
        </div>
      </div>
    </section>

    <section class="mx-auto w-full max-w-container-max px-margin-mobile py-16 md:px-gutter">
      <div class="flex w-full min-w-0 flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div class="w-full min-w-0 md:max-w-2xl">
          <p class="watch-accent-text w-full text-xs font-bold uppercase tracking-[0.2em]">
            Featured collections
          </p>
          <h2 class="mt-3 w-full font-display text-5xl font-semibold leading-none text-primary md:text-6xl">
            Chọn theo khoảnh khắc.
          </h2>
        </div>
        <RouterLink to="/products" class="watch-accent-strong w-fit text-sm font-bold uppercase tracking-[0.14em]">
          Xem tất cả
        </RouterLink>
      </div>

      <div class="mt-10 grid w-full min-w-0 gap-5 md:grid-cols-3">
        <RouterLink
          v-for="collection in collectionHighlights"
          :key="collection.slug"
          :to="{ name: 'products', query: collection.query }"
          class="watch-collection-card flex min-h-72 w-full min-w-0 flex-col justify-end rounded-[var(--radius-watch-lg)] border border-border bg-surface p-6 shadow-[var(--shadow-watch-soft)] transition-all duration-300 hover:-translate-y-1 hover:scale-[1.015] hover:border-[rgb(161_98_7/0.42)] hover:shadow-[var(--shadow-watch-floating)]"
        >
          <p class="w-full font-display text-4xl font-semibold text-primary">
            {{ collection.name }}
          </p>
          <p class="mt-3 w-full text-sm leading-6 text-on-surface-variant">
            {{ collection.copy }}
          </p>
        </RouterLink>
      </div>
    </section>

    <section ref="campaignBannerRef" class="relative min-h-[72vh] w-full overflow-hidden bg-[var(--watch-color-ink-950)]">
      <img
        ref="campaignImageRef"
        src="https://images.unsplash.com/photo-1560379353-319e3563cf54?auto=format&fit=crop&w=1800&q=85"
        alt="Đồng hồ phối cùng suit nâu trong bộ sưu tập Office Style"
        class="absolute inset-0 h-[116%] w-full object-cover"
        loading="lazy"
      />
      <div class="absolute inset-0 bg-[linear-gradient(90deg,rgb(12_10_9/0.88),rgb(12_10_9/0.52),rgb(12_10_9/0.18))]"></div>
      <div class="relative z-10 mx-auto flex min-h-[72vh] w-full max-w-container-max items-end px-margin-mobile py-16 md:px-gutter lg:items-center">
        <div class="w-full max-w-2xl min-w-0">
          <p class="watch-accent-text w-full text-xs font-bold uppercase tracking-[0.22em]">
            Campaign edit
          </p>
          <h2 class="mt-4 w-full font-display text-[clamp(3.5rem,9vw,8rem)] font-semibold leading-[0.86] text-white">
            Office Style
          </h2>
          <p class="mt-6 w-full text-base leading-8 text-[rgb(250_250_249/0.78)] md:text-lg">
            Những mẫu đồng hồ đủ kín đáo cho phòng họp, đủ sắc nét cho bữa tối sau giờ làm. Tập trung vào dây da, mặt số trầm và tỉ lệ dễ đeo mỗi ngày.
          </p>
          <RouterLink
            :to="{ name: 'products', query: { collection: 'office-style' } }"
            class="mt-8 inline-flex min-h-12 items-center justify-center rounded-[var(--radius-watch-md)] bg-[var(--accent-primary)] px-6 text-sm font-bold uppercase tracking-[0.14em] text-white transition-colors hover:bg-[var(--accent-primary-hover)]"
          >
            Khám phá Office Style
          </RouterLink>
        </div>
      </div>
    </section>

    <section class="border-y border-[rgb(214_178_114/0.18)] bg-[var(--watch-color-ink-950)] text-[var(--watch-color-ivory-100)]">
      <div class="mx-auto w-full max-w-container-max px-margin-mobile py-16 md:px-gutter">
        <div class="flex w-full min-w-0 flex-col gap-4 md:flex-row md:items-end md:justify-between">
          <div class="w-full min-w-0 md:max-w-2xl">
            <p class="watch-accent-text w-full text-xs font-bold uppercase tracking-[0.2em]">
              Featured products
            </p>
            <h2 class="mt-3 w-full font-display text-5xl font-semibold leading-none text-white md:text-6xl">
              Đang được quan tâm.
            </h2>
          </div>
        </div>

        <div v-if="isLoadingProducts" class="mt-10 w-full rounded-[var(--radius-watch-lg)] border border-[rgb(214_178_114/0.2)] bg-white/5 p-10 text-center text-[rgb(250_250_249/0.72)]">
          Đang tải sản phẩm nổi bật...
        </div>
        <ProductShowcaseGrid v-else-if="featuredProducts.length" class="mt-10" :products="featuredProducts" />
        <div v-else class="mt-10 w-full rounded-[var(--radius-watch-lg)] border border-[rgb(214_178_114/0.2)] bg-white/5 p-10 text-center text-[rgb(250_250_249/0.72)]">
          Chưa có sản phẩm nổi bật.
        </div>
      </div>
    </section>

    <section class="mx-auto w-full max-w-container-max px-margin-mobile py-16 md:px-gutter">
      <div class="flex w-full min-w-0 flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div class="w-full min-w-0 md:max-w-2xl">
          <p class="watch-accent-text w-full text-xs font-bold uppercase tracking-[0.2em]">
            Why Watchora
          </p>
          <h2 class="mt-3 w-full font-display text-5xl font-semibold leading-none text-primary md:text-6xl">
            Mua đồng hồ đẹp, an tâm hơn.
          </h2>
        </div>
        <p class="w-full min-w-0 text-sm leading-7 text-on-surface-variant md:max-w-md md:text-right">
          Những cam kết cơ bản nhưng quan trọng: rõ chính sách, rõ chi phí, rõ trách nhiệm sau mua.
        </p>
      </div>

      <div class="mt-10 grid w-full min-w-0 gap-5 md:grid-cols-2 lg:grid-cols-4">
        <article
          v-for="item in uspItems"
          :key="item.title"
          class="watch-usp-card flex min-h-56 w-full min-w-0 flex-col rounded-[var(--radius-watch-lg)] border border-border bg-surface p-6 shadow-[var(--shadow-watch-soft)]"
        >
          <div class="flex h-12 w-12 items-center justify-center rounded-full border border-[rgb(161_98_7/0.26)] bg-[rgb(161_98_7/0.08)] text-[var(--accent-primary)]">
            <span class="material-symbols-outlined text-[24px]" aria-hidden="true">{{ item.icon }}</span>
          </div>
          <h3 class="mt-6 w-full font-display text-3xl font-semibold leading-tight text-primary">
            {{ item.title }}
          </h3>
          <p class="mt-3 w-full text-sm leading-7 text-on-surface-variant">
            {{ item.copy }}
          </p>
        </article>
      </div>
    </section>

    <section class="mx-auto w-full max-w-container-max px-margin-mobile py-16 md:px-gutter">
      <p class="watch-accent-text w-full text-center text-xs font-bold uppercase tracking-[0.2em]">
        Partner brands
      </p>
      <div class="mt-8 grid w-full min-w-0 grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
        <div
          v-for="brand in visibleBrands"
          :key="brand.id ?? brand.slug"
          class="flex min-h-24 w-full min-w-0 items-center justify-center rounded-[var(--radius-watch-md)] border border-border bg-surface px-4 text-center"
        >
          <img
            v-if="brand.logo"
            :src="brand.logo"
            :alt="brand.name"
            class="max-h-10 max-w-full object-contain"
          />
          <span v-else class="w-full truncate font-display text-2xl font-semibold text-primary">
            {{ brand.name }}
          </span>
        </div>
      </div>
    </section>

    <section class="border-t border-border bg-surface">
      <div class="mx-auto grid w-full max-w-container-max gap-10 px-margin-mobile py-16 md:px-gutter lg:grid-cols-[0.8fr_1.2fr]">
        <div class="w-full min-w-0">
          <p class="watch-accent-text w-full text-xs font-bold uppercase tracking-[0.2em]">
            FAQ
          </p>
          <h2 class="mt-3 w-full font-display text-5xl font-semibold leading-none text-primary md:text-6xl">
            Mua đồng hồ, rõ ràng từ đầu.
          </h2>
        </div>
        <div class="w-full min-w-0 divide-y divide-border border-y border-border">
          <details
            v-for="item in faqs"
            :key="item.question"
            class="group w-full py-5"
          >
            <summary class="flex w-full cursor-pointer list-none items-center justify-between gap-4 font-semibold text-primary">
              <span class="min-w-0">{{ item.question }}</span>
              <span class="shrink-0 text-xl transition-transform group-open:rotate-45">+</span>
            </summary>
            <p class="mt-3 w-full text-sm leading-7 text-on-surface-variant">
              {{ item.answer }}
            </p>
          </details>
        </div>
      </div>
    </section>
  </main>
</template>
