<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import CourseCard from '../components/CourseCard.vue'
import { getCertificationById } from '../services/api'
import { formatCurrency } from '../utils/formatCurrency'

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const error = ref('')
const certification = ref(null)
const courses = ref([])
const instructors = ref([])
const stats = ref({ total_courses: 0, total_students: 0 })

const filters = reactive({
  rating: 'all',
  price: 'all',
  level: 'all',
})

const certificationAccent = computed(() => certification.value?.accent_color || '#FF6B4A')

const certificationStyle = computed(() => ({
  '--cert-accent': certificationAccent.value,
}))

const badgeStyle = computed(() => ({
  ...certificationStyle.value,
  backgroundImage: certification.value?.badge_image_url
    ? `linear-gradient(135deg, rgba(14, 27, 46, 0.15), rgba(14, 27, 46, 0.72)), url("${certification.value.badge_image_url}")`
    : 'linear-gradient(135deg, rgba(14, 27, 46, 0.92), rgba(30, 47, 74, 0.96))',
}))

const initials = (name) => String(name ?? 'EM')
  .split(' ')
  .filter(Boolean)
  .slice(0, 2)
  .map((part) => part[0])
  .join('')
  .toUpperCase()

const finalPrice = (course) => Number(course.final_price ?? course.discount_price ?? course.price ?? 0)

const courseHighlights = (course) => [
  `Theo lộ trình ${certification.value?.provider ?? 'certification'} thực tế`,
  `Cấp độ ${course.level ?? 'beginner'} với bài học có cấu trúc`,
  'Có thể thêm vào giỏ và học ngay sau thanh toán',
]

const filteredCourses = computed(() => courses.value.filter((course) => {
  const rating = Number(course.rating_avg ?? 0)
  const price = finalPrice(course)

  if (filters.rating !== 'all' && rating < Number(filters.rating)) return false
  if (filters.level !== 'all' && course.level !== filters.level) return false

  if (filters.price === 'under500' && price >= 500000) return false
  if (filters.price === '500to1000' && (price < 500000 || price > 1000000)) return false
  if (filters.price === 'over1000' && price <= 1000000) return false

  return true
}))

const fetchCertification = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await getCertificationById(route.params.id)
    const payload = response.data.data

    certification.value = payload.certification
    courses.value = payload.courses?.items ?? []
    instructors.value = payload.instructors ?? []
    stats.value = payload.stats ?? { total_courses: 0, total_students: 0 }
  } catch (requestError) {
    error.value = requestError.response?.data?.message ?? 'Không thể tải thông tin chứng chỉ.'
  } finally {
    loading.value = false
  }
}

watch(() => route.params.id, fetchCertification)

onMounted(fetchCertification)
</script>

<template>
  <main class="w-full min-w-0 bg-background font-body text-on-surface">
    <section v-if="loading" class="mx-auto flex min-h-[520px] w-full max-w-container-max items-center justify-center px-margin-mobile py-xl md:px-gutter">
      <div class="w-full max-w-md animate-pulse rounded-xl border border-surface-variant bg-surface p-lg">
        <div class="h-12 w-12 rounded-lg bg-surface-container-highest"></div>
        <div class="mt-md h-8 w-3/4 rounded bg-surface-container-highest"></div>
        <div class="mt-sm h-4 w-full rounded bg-surface-container-highest"></div>
        <div class="mt-xs h-4 w-2/3 rounded bg-surface-container-highest"></div>
      </div>
    </section>

    <section v-else-if="error" class="mx-auto flex min-h-[520px] w-full max-w-container-max items-center justify-center px-margin-mobile py-xl md:px-gutter">
      <div class="w-full max-w-md rounded-xl border border-error/40 bg-error/10 p-lg text-center">
        <span class="material-symbols-outlined text-5xl text-error">error</span>
        <h1 class="mt-sm w-full font-display text-headline-sm font-semibold text-on-surface">Không tải được chứng chỉ</h1>
        <p class="mt-xs w-full text-body-sm text-error">{{ error }}</p>
        <button class="mt-md w-full rounded-lg bg-primary px-md py-3 font-display text-body-sm font-semibold text-on-primary" type="button" @click="fetchCertification">
          Thử lại
        </button>
      </div>
    </section>

    <template v-else-if="certification">
      <section class="relative w-full overflow-hidden bg-surface-container-low py-xl" :style="certificationStyle">
        <div class="hero-glow"></div>
        <div class="relative z-10 mx-auto grid w-full min-w-0 max-w-container-max grid-cols-1 gap-lg px-margin-mobile md:px-gutter lg:grid-cols-[1fr_22rem]">
          <div class="w-full min-w-0">
            <div class="certification-badge flex h-20 w-20 shrink-0 items-center justify-center rounded-xl border bg-cover bg-center shadow-lg" :style="badgeStyle">
              <span class="material-symbols-outlined text-4xl">{{ certification.icon || 'workspace_premium' }}</span>
            </div>
            <p class="certification-accent-text mt-md w-full font-mono text-label-mono uppercase tracking-widest">{{ certification.provider }}</p>
            <h1 class="mt-xs w-full max-w-4xl font-display text-display-lg-mobile font-bold leading-tight text-on-background md:text-display-lg">{{ certification.name }}</h1>
            <p class="mt-md w-full max-w-3xl text-body-lg leading-8 text-on-surface-variant">{{ certification.description }}</p>
            <div class="mt-lg flex w-full min-w-0 flex-wrap gap-sm">
              <a
                v-if="certification.external_link"
                class="certification-primary-action inline-flex items-center justify-center gap-xs rounded-lg px-md py-3 font-display text-body-sm font-semibold hover:opacity-90"
                :href="certification.external_link"
                target="_blank"
                rel="noopener noreferrer"
              >
                Đăng ký thi
                <span class="material-symbols-outlined text-[18px]">open_in_new</span>
              </a>
              <button class="inline-flex items-center justify-center gap-xs rounded-lg border border-surface-variant px-md py-3 font-display text-body-sm font-semibold text-on-surface hover:bg-surface" type="button" @click="router.push('/certifications')">
                Tất cả chứng chỉ
              </button>
            </div>
          </div>

          <aside class="certification-panel w-full min-w-0 rounded-xl border bg-surface/80 p-md backdrop-blur">
            <p class="certification-accent-text w-full font-mono text-label-mono uppercase tracking-widest">Certification stats</p>
            <div class="mt-md grid w-full min-w-0 grid-cols-2 gap-sm">
              <div class="w-full rounded-lg bg-background p-sm">
                <p class="w-full font-display text-2xl font-bold text-on-surface">{{ stats.total_courses }}</p>
                <p class="w-full text-body-sm text-on-surface-variant">khóa liên quan</p>
              </div>
              <div class="w-full rounded-lg bg-background p-sm">
                <p class="w-full font-display text-2xl font-bold text-on-surface">{{ Number(stats.total_students ?? 0).toLocaleString('vi-VN') }}</p>
                <p class="w-full text-body-sm text-on-surface-variant">học viên</p>
              </div>
            </div>
          </aside>
        </div>
      </section>

      <section class="w-full bg-background py-xl">
        <div class="mx-auto w-full min-w-0 max-w-container-max px-margin-mobile md:px-gutter">
          <div class="mb-lg w-full min-w-0">
            <p class="certification-accent-text w-full font-mono text-label-mono uppercase tracking-widest" :style="certificationStyle">Instructor spotlight</p>
            <h2 class="mt-xs w-full font-display text-headline-md font-semibold text-on-background">Giảng viên hàng đầu về {{ certification.name }}</h2>
          </div>

          <div v-if="instructors.length" class="grid w-full min-w-0 grid-cols-1 gap-md md:grid-cols-2 lg:grid-cols-4">
            <article v-for="instructor in instructors" :key="instructor.id" class="course-card-element flex w-full min-w-0 flex-col items-center rounded-xl border border-surface-variant bg-surface p-md text-center">
              <img v-if="instructor.avatar_url" :src="instructor.avatar_url" :alt="instructor.name" class="h-20 w-20 shrink-0 rounded-full border border-surface-variant object-cover" />
              <div v-else class="certification-avatar-fallback flex h-20 w-20 shrink-0 items-center justify-center rounded-full border bg-background font-display text-xl font-bold" :style="certificationStyle">{{ initials(instructor.name) }}</div>
              <div class="mt-md w-full min-w-0">
                <h3 class="w-full truncate font-display text-[20px] font-semibold text-on-surface">{{ instructor.name }}</h3>
                <p class="certification-accent-text mt-1 w-full truncate font-mono text-label-mono uppercase" :style="certificationStyle">{{ instructor.specialty || certification.provider }}</p>
              </div>
              <div class="mt-md grid w-full min-w-0 grid-cols-3 gap-sm border-t border-surface-variant pt-md">
                <div class="w-full min-w-0">
                  <p class="w-full font-display text-lg font-semibold text-on-surface">{{ instructor.courses_count }}</p>
                  <p class="w-full text-[11px] text-on-surface-variant">khóa học</p>
                </div>
                <div class="w-full min-w-0">
                  <p class="w-full font-display text-lg font-semibold text-on-surface">{{ Number(instructor.total_students ?? 0).toLocaleString('vi-VN') }}</p>
                  <p class="w-full text-[11px] text-on-surface-variant">học viên</p>
                </div>
                <div class="w-full min-w-0">
                  <p class="w-full font-display text-lg font-semibold text-tertiary">{{ Number(instructor.rating_avg ?? 0).toFixed(1) }}</p>
                  <p class="w-full text-[11px] text-on-surface-variant">rating</p>
                </div>
              </div>
            </article>
          </div>

          <p v-else class="w-full rounded-xl border border-dashed border-surface-variant bg-surface p-md text-on-surface-variant">Chưa có giảng viên nổi bật cho chứng chỉ này.</p>
        </div>
      </section>

      <section class="w-full bg-surface-container-low py-xl">
        <div class="mx-auto grid w-full min-w-0 max-w-container-max grid-cols-1 gap-lg px-margin-mobile md:px-gutter lg:grid-cols-[18rem_1fr]">
          <aside class="w-full min-w-0 self-start rounded-xl border border-surface-variant bg-surface p-md lg:sticky lg:top-24">
            <p class="certification-accent-text w-full font-mono text-label-mono uppercase tracking-widest" :style="certificationStyle">Filters</p>
            <h2 class="mt-xs w-full font-display text-headline-sm font-semibold text-on-surface">Lọc khóa học</h2>

            <div class="mt-md w-full min-w-0 space-y-md">
              <label class="block w-full min-w-0">
                <span class="block w-full text-body-sm font-medium text-on-surface">Rating</span>
                <select v-model="filters.rating" class="mt-xs w-full rounded-lg border border-surface-variant bg-background px-sm py-3 text-on-surface outline-none focus:border-primary">
                  <option value="all">Tất cả</option>
                  <option value="4">4.0+ sao</option>
                  <option value="4.5">4.5+ sao</option>
                </select>
              </label>

              <label class="block w-full min-w-0">
                <span class="block w-full text-body-sm font-medium text-on-surface">Khoảng giá</span>
                <select v-model="filters.price" class="mt-xs w-full rounded-lg border border-surface-variant bg-background px-sm py-3 text-on-surface outline-none focus:border-primary">
                  <option value="all">Tất cả</option>
                  <option value="under500">Dưới {{ formatCurrency(500000) }}</option>
                  <option value="500to1000">{{ formatCurrency(500000) }} - {{ formatCurrency(1000000) }}</option>
                  <option value="over1000">Trên {{ formatCurrency(1000000) }}</option>
                </select>
              </label>

              <label class="block w-full min-w-0">
                <span class="block w-full text-body-sm font-medium text-on-surface">Trình độ</span>
                <select v-model="filters.level" class="mt-xs w-full rounded-lg border border-surface-variant bg-background px-sm py-3 text-on-surface outline-none focus:border-primary">
                  <option value="all">Tất cả</option>
                  <option value="beginner">Beginner</option>
                  <option value="intermediate">Intermediate</option>
                  <option value="advanced">Advanced</option>
                </select>
              </label>
            </div>
          </aside>

          <div class="w-full min-w-0">
            <div class="mb-md flex w-full min-w-0 flex-col gap-sm md:flex-row md:items-end md:justify-between">
              <div class="w-full min-w-0">
                <p class="certification-accent-text w-full font-mono text-label-mono uppercase tracking-widest" :style="certificationStyle">Learning path</p>
                <h2 class="mt-xs w-full font-display text-headline-md font-semibold text-on-background">Khóa học liên quan</h2>
              </div>
              <p class="w-full text-body-sm text-on-surface-variant md:w-auto md:shrink-0">{{ filteredCourses.length }} / {{ courses.length }} khóa học</p>
            </div>

            <div v-if="filteredCourses.length" class="grid w-full min-w-0 grid-cols-1 justify-items-center gap-lg xl:grid-cols-2 2xl:grid-cols-3">
              <CourseCard
                v-for="course in filteredCourses"
                :key="course.id"
                :course-id="course.id"
                :thumbnail="course.thumbnail_url || ''"
                :level="course.level || 'beginner'"
                :tech-tag="course.category?.name || certification.provider"
                :category-accent="course.category?.accent_color || certification.accent_color || '#FF6B4A'"
                :title="course.title"
                :instructor="course.instructor?.name || 'EduMarket Instructor'"
                :rating="Number(course.rating_avg ?? 0)"
                :price="finalPrice(course)"
                :description="course.description || 'Khóa học giúp bạn chuẩn bị kiến thức thực tế cho chứng chỉ này.'"
                :highlights="courseHighlights(course)"
              />
            </div>

            <p v-else class="w-full rounded-xl border border-dashed border-surface-variant bg-surface p-lg text-center text-on-surface-variant">Không có khóa học phù hợp với bộ lọc hiện tại.</p>
          </div>
        </div>
      </section>

      <section class="w-full bg-background py-xl">
        <div class="mx-auto w-full min-w-0 max-w-container-max px-margin-mobile md:px-gutter">
          <div class="w-full min-w-0 rounded-xl border border-surface-variant bg-surface p-lg">
            <p class="certification-accent-text w-full font-mono text-label-mono uppercase tracking-widest" :style="certificationStyle">Exam info</p>
            <h2 class="mt-xs w-full font-display text-headline-md font-semibold text-on-background">Thông tin kỳ thi</h2>
            <p class="mt-md w-full whitespace-pre-line text-body-md leading-7 text-on-surface-variant">{{ certification.exam_info || 'Thông tin kỳ thi đang được cập nhật.' }}</p>
          </div>
        </div>
      </section>
    </template>
  </main>
</template>

<style scoped>
.certification-badge {
  border-color: color-mix(in srgb, var(--cert-accent) 70%, var(--border-subtle));
  color: var(--cert-accent);
  box-shadow: 0 18px 42px color-mix(in srgb, var(--cert-accent) 24%, transparent);
  transform: perspective(600px) rotateY(0deg) translateZ(0);
  transition:
    transform 240ms cubic-bezier(0.16, 1, 0.3, 1),
    box-shadow 240ms cubic-bezier(0.16, 1, 0.3, 1);
}

.certification-badge:hover {
  transform: perspective(600px) rotateY(8deg) translateZ(14px);
  box-shadow: 0 22px 50px color-mix(in srgb, var(--cert-accent) 32%, transparent);
}

.certification-accent-text {
  color: var(--cert-accent);
}

.certification-primary-action {
  background-color: var(--cert-accent);
  color: var(--text-primary);
}

.certification-panel {
  border-color: color-mix(in srgb, var(--cert-accent) 40%, var(--border-subtle));
}

.certification-avatar-fallback {
  border-color: color-mix(in srgb, var(--cert-accent) 60%, var(--border-subtle));
  color: var(--cert-accent);
}

@media (prefers-reduced-motion: reduce) {
  .certification-badge,
  .certification-badge:hover {
    transform: none;
    transition: none;
  }
}
</style>
