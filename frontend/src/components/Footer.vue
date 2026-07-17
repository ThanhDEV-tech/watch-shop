<script setup>
import { computed, onMounted, ref } from 'vue'
import { getCategories } from '../services/api'

const categories = ref([])

const fallbackCategories = [
  { name: 'Dress Watch', slug: 'dress-watch' },
  { name: 'Sport Watch', slug: 'sport-watch' },
  { name: 'Casual Watch', slug: 'casual-watch' },
]

const policyLinks = [
  'Chính sách bảo hành',
  'Đổi trả trong 7 ngày',
  'Vận chuyển & phí giao hàng',
  'Hướng dẫn chọn size',
]

const socialLinks = [
  { label: 'Instagram', icon: 'photo_camera' },
  { label: 'Facebook', icon: 'public' },
  { label: 'TikTok', icon: 'play_circle' },
]

const footerCategories = computed(() => {
  const allowedSlugs = new Set(fallbackCategories.map((category) => category.slug))
  const apiCategories = categories.value.filter((category) => allowedSlugs.has(category.slug))

  return apiCategories.length ? apiCategories : fallbackCategories
})

const fetchCategories = async () => {
  try {
    const response = await getCategories()
    categories.value = response.data.data ?? []
  } catch {
    categories.value = []
  }
}

onMounted(fetchCategories)
</script>

<template>
  <footer class="w-full border-t border-border bg-[var(--watch-color-ink-950)] px-margin-mobile py-xl font-body text-[var(--watch-color-ivory-100)] md:px-gutter">
    <div class="mx-auto w-full max-w-container-max">
      <div class="grid w-full min-w-0 gap-lg md:grid-cols-2 lg:grid-cols-[1.25fr_0.75fr_0.85fr_0.75fr]">
        <div class="w-full min-w-0">
          <RouterLink to="/" class="block w-full font-display text-4xl font-semibold text-[var(--watch-color-gold-300)]">
            Watchora
          </RouterLink>
          <p class="mt-md w-full max-w-md text-body-sm leading-7 text-[rgb(250_250_249/0.72)]">
            Shop đồng hồ thời trang nam nữ theo tinh thần tối giản, sang trọng và đáng tin cậy. Watchora chọn các mẫu dễ đeo, dễ tặng, có giá trị sử dụng lâu dài.
          </p>
          <div class="mt-md flex w-full min-w-0 flex-wrap gap-sm">
            <a
              v-for="social in socialLinks"
              :key="social.label"
              href="#"
              class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-[rgb(214_178_114/0.28)] text-[var(--watch-color-gold-300)] transition-colors hover:border-[var(--watch-color-gold-300)] hover:bg-[rgb(214_178_114/0.08)]"
              :aria-label="social.label"
            >
              <span class="material-symbols-outlined text-[20px]">{{ social.icon }}</span>
            </a>
          </div>
        </div>

        <div class="w-full min-w-0">
          <h4 class="w-full font-display text-2xl font-semibold text-white">Danh mục</h4>
          <ul class="mt-md flex w-full min-w-0 flex-col gap-sm">
            <li v-for="category in footerCategories" :key="category.slug" class="w-full min-w-0">
              <RouterLink
                :to="{ name: 'products', query: { category: category.slug } }"
                class="w-full text-body-sm text-[rgb(250_250_249/0.72)] transition-colors hover:text-[var(--watch-color-gold-300)]"
              >
                {{ category.name }}
              </RouterLink>
            </li>
          </ul>
        </div>

        <div class="w-full min-w-0">
          <h4 class="w-full font-display text-2xl font-semibold text-white">Chính sách</h4>
          <ul class="mt-md flex w-full min-w-0 flex-col gap-sm">
            <li v-for="link in policyLinks" :key="link" class="w-full min-w-0">
              <a href="#" class="w-full text-body-sm text-[rgb(250_250_249/0.72)] transition-colors hover:text-[var(--watch-color-gold-300)]">
                {{ link }}
              </a>
            </li>
          </ul>
        </div>

        <div class="w-full min-w-0">
          <h4 class="w-full font-display text-2xl font-semibold text-white">Liên hệ</h4>
          <address class="mt-md w-full not-italic text-body-sm leading-7 text-[rgb(250_250_249/0.72)]">
            0900 000 888<br />
            hello@watchora.vn<br />
            24 Nguyễn Huệ, Quận 1, TP.HCM
          </address>
        </div>
      </div>

      <div class="mt-xl flex w-full min-w-0 flex-col gap-md border-t border-[rgb(214_178_114/0.18)] pt-lg md:flex-row md:items-center md:justify-between">
        <p class="w-full min-w-0 text-center font-mono text-label-mono text-[rgb(250_250_249/0.58)] md:w-auto md:text-left">
          © 2026 Watchora. Đồng hồ thời trang được tuyển chọn.
        </p>
        <div class="flex w-full min-w-0 flex-wrap justify-center gap-md md:w-auto">
          <a
            v-for="link in ['Điều khoản', 'Bảo mật', 'Hỗ trợ']"
            :key="link"
            href="#"
            class="font-mono text-label-mono text-[rgb(250_250_249/0.58)] transition-colors hover:text-[var(--watch-color-gold-300)]"
          >
            {{ link }}
          </a>
        </div>
      </div>
    </div>
  </footer>
</template>
