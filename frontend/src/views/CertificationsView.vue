<script setup>
import { onMounted, ref } from 'vue'
import BaseModal from '../components/ui/BaseModal.vue'
import { getCertifications } from '../services/api'

const certifications = ref([])
const selectedCertification = ref(null)
const loading = ref(true)
const error = ref('')

const fetchCertifications = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await getCertifications()
    certifications.value = response.data.data ?? []
  } catch (requestError) {
    error.value = requestError.response?.data?.message ?? 'Không thể tải danh sách chứng chỉ.'
  } finally {
    loading.value = false
  }
}

const hasRelatedCourses = (certification) => Number(certification.courses_count ?? 0) > 0

const certificationStyle = (certification) => ({
  '--cert-accent': certification.accent_color || '#FF6B4A',
})

const badgeStyle = (certification) => ({
  ...certificationStyle(certification),
  backgroundImage: certification.badge_image_url
    ? `linear-gradient(135deg, rgba(14, 27, 46, 0.18), rgba(14, 27, 46, 0.68)), url("${certification.badge_image_url}")`
    : 'linear-gradient(135deg, rgba(14, 27, 46, 0.92), rgba(30, 47, 74, 0.96))',
})

const openFallbackModal = (certification) => {
  selectedCertification.value = certification
}

const closeModal = () => {
  selectedCertification.value = null
}

onMounted(fetchCertifications)
</script>

<template>
  <main class="w-full min-w-0 py-xl font-body">
    <section class="mx-auto w-full min-w-0 max-w-container-max px-margin-mobile md:px-gutter">
      <div class="w-full min-w-0 max-w-3xl">
        <p class="w-full font-mono text-label-mono uppercase tracking-widest text-primary">Professional paths</p>
        <h1 class="mt-sm w-full font-display text-display-lg-mobile font-bold text-on-background md:text-display-lg">Chứng chỉ nghề nghiệp</h1>
        <p class="mt-md w-full text-body-lg text-on-surface-variant">Khám phá các chứng chỉ được công nhận trong ngành để định hướng lộ trình học tập và chuẩn bị cho kỳ thi tiếp theo.</p>
      </div>

      <div v-if="loading" class="mt-xl grid w-full min-w-0 grid-cols-1 gap-md md:grid-cols-2 lg:grid-cols-3">
        <div v-for="index in 6" :key="index" class="h-72 w-full animate-pulse rounded-lg border border-surface-variant bg-surface" />
      </div>

      <div v-else-if="error" class="mt-xl flex min-h-[320px] w-full min-w-0 items-center justify-center rounded-lg border border-error/40 bg-error/10 p-md text-center">
        <div class="w-full min-w-0 max-w-md">
          <span class="material-symbols-outlined text-5xl text-error">error</span>
          <p class="mt-sm w-full text-body-md text-error">{{ error }}</p>
          <button class="mt-md w-full rounded-lg bg-primary px-md py-3 font-display text-body-sm font-semibold text-on-primary" type="button" @click="fetchCertifications">Thử lại</button>
        </div>
      </div>

      <div v-else-if="!certifications.length" class="mt-xl flex min-h-[320px] w-full min-w-0 items-center justify-center rounded-lg border border-dashed border-surface-variant bg-surface p-md text-center">
        <div class="w-full min-w-0 max-w-md">
          <span class="material-symbols-outlined text-6xl text-primary">workspace_premium</span>
          <h2 class="mt-sm w-full font-display text-headline-sm font-semibold text-on-surface">Chưa có chứng chỉ</h2>
          <p class="mt-xs w-full text-body-sm text-on-surface-variant">Danh sách chứng chỉ đang được cập nhật.</p>
        </div>
      </div>

      <div v-else class="mt-xl grid w-full min-w-0 grid-cols-1 gap-md md:grid-cols-2 lg:grid-cols-3">
        <article v-for="certification in certifications" :key="certification.id" class="certification-card course-card-element flex w-full min-w-0 flex-col rounded-lg border bg-surface p-lg" :style="certificationStyle(certification)">
          <div class="certification-badge flex h-16 w-16 min-w-16 shrink-0 items-center justify-center rounded-xl border bg-cover bg-center shadow-lg" :style="badgeStyle(certification)">
            <span class="material-symbols-outlined block shrink-0 whitespace-nowrap text-3xl leading-none" aria-hidden="true">{{ certification.icon || 'verified' }}</span>
          </div>

          <div class="mt-md w-full min-w-0 flex-1">
            <div class="flex w-full min-w-0 flex-wrap items-center justify-between gap-sm">
              <p class="certification-accent-text font-mono text-label-mono uppercase tracking-wider">{{ certification.provider }}</p>
              <span v-if="hasRelatedCourses(certification)" class="shrink-0 rounded border border-surface-variant px-sm py-xs font-mono text-label-mono text-on-surface-variant">{{ certification.courses_count }} khóa</span>
            </div>
            <h2 class="mt-xs w-full font-display text-headline-sm font-semibold text-on-surface">{{ certification.name }}</h2>
            <p class="mt-sm w-full text-body-sm leading-6 text-on-surface-variant">{{ certification.description }}</p>
          </div>

          <RouterLink
            v-if="hasRelatedCourses(certification)"
            class="certification-action mt-lg inline-flex w-full items-center justify-center gap-xs rounded-lg border px-md py-3 font-display text-body-sm font-semibold transition-colors"
            :to="{ name: 'certification-detail', params: { id: certification.id } }"
          >
            Tìm hiểu thêm
            <span class="material-symbols-outlined shrink-0 text-[18px]">arrow_forward</span>
          </RouterLink>

          <button
            v-else
            class="certification-action mt-lg inline-flex w-full items-center justify-center gap-xs rounded-lg border px-md py-3 font-display text-body-sm font-semibold transition-colors"
            type="button"
            @click="openFallbackModal(certification)"
          >
            Tìm hiểu thêm
            <span class="material-symbols-outlined shrink-0 text-[18px]">arrow_forward</span>
          </button>
        </article>
      </div>
    </section>

    <BaseModal v-if="selectedCertification" max-width="lg" aria-labelledby="certification-dialog-title" backdrop-blur @close="closeModal">
      <section class="certification-modal max-h-[85vh] w-full min-w-0 overflow-y-auto rounded-lg border bg-surface p-lg shadow-2xl" :style="certificationStyle(selectedCertification)">
        <div class="flex w-full min-w-0 items-start justify-between gap-md">
          <div class="w-full min-w-0">
            <p class="certification-accent-text w-full font-mono text-label-mono uppercase tracking-wider">{{ selectedCertification.provider }}</p>
            <h2 id="certification-dialog-title" class="mt-xs w-full font-display text-headline-sm font-semibold text-on-surface">{{ selectedCertification.name }}</h2>
          </div>
          <button class="material-symbols-outlined h-10 w-10 shrink-0 rounded-lg text-on-surface-variant hover:bg-surface-container-highest hover:text-on-surface" type="button" aria-label="Đóng" @click="closeModal">close</button>
        </div>

        <div class="mt-lg w-full min-w-0 rounded-lg border border-surface-variant bg-background p-md">
          <h3 class="w-full font-display text-body-md font-semibold text-on-surface">Thông tin kỳ thi</h3>
          <p class="mt-sm w-full whitespace-pre-line text-body-sm leading-6 text-on-surface-variant">{{ selectedCertification.exam_info }}</p>
          <a
            v-if="selectedCertification.external_link"
            class="mt-md inline-flex w-full items-center justify-center gap-xs rounded-lg border border-primary px-md py-2 font-display text-body-sm font-semibold text-primary hover:bg-primary hover:text-on-primary"
            :href="selectedCertification.external_link"
            target="_blank"
            rel="noopener noreferrer"
          >
            Trang thi chính thức
            <span class="material-symbols-outlined text-[18px]">open_in_new</span>
          </a>
        </div>

        <p class="mt-md w-full rounded-lg border border-dashed border-surface-variant p-sm text-body-sm text-on-surface-variant">Chưa có khóa học liên quan cho chứng chỉ này. Khi có course được gắn chứng chỉ, nút này sẽ mở trang chi tiết đầy đủ.</p>

        <button class="mt-lg w-full rounded-lg border border-surface-variant px-md py-3 font-display text-body-sm font-semibold text-on-surface hover:bg-surface-container-highest" type="button" @click="closeModal">Đóng</button>
      </section>
    </BaseModal>
  </main>
</template>

<style scoped>
.certification-card {
  border-color: color-mix(in srgb, var(--cert-accent) 42%, var(--border-subtle));
}

.certification-modal {
  border-color: color-mix(in srgb, var(--cert-accent) 44%, var(--border-subtle));
}

.certification-badge {
  border-color: color-mix(in srgb, var(--cert-accent) 70%, var(--border-subtle));
  color: var(--cert-accent);
  box-shadow: 0 14px 28px color-mix(in srgb, var(--cert-accent) 22%, transparent);
  transform: perspective(600px) rotateY(0deg) translateZ(0);
  transition:
    transform 240ms cubic-bezier(0.16, 1, 0.3, 1),
    box-shadow 240ms cubic-bezier(0.16, 1, 0.3, 1);
}

.certification-card:hover .certification-badge {
  transform: perspective(600px) rotateY(8deg) translateZ(12px);
  box-shadow: 0 18px 38px color-mix(in srgb, var(--cert-accent) 30%, transparent);
}

.certification-accent-text {
  color: var(--cert-accent);
}

.certification-action {
  border-color: var(--cert-accent);
  color: var(--cert-accent);
}

.certification-action:hover {
  background-color: var(--cert-accent);
  color: var(--text-primary);
}

@media (prefers-reduced-motion: reduce) {
  .certification-badge,
  .certification-card:hover .certification-badge {
    transform: none;
    transition: none;
  }
}
</style>
