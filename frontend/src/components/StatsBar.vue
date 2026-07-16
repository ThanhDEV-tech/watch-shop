<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { getPublicStats } from '../services/api'

const sectionRef = ref(null)
const isLoading = ref(true)
const errorMessage = ref('')
const hasAnimated = ref(false)
const isVisible = ref(false)
const stats = reactive({
  total_courses: 0,
  total_students: 0,
  total_instructors: 0,
  avg_rating: 0,
})
const displayValues = reactive({
  total_courses: 0,
  total_students: 0,
  total_instructors: 0,
  avg_rating: 0,
})

let observer = null
let animationFrame = null

const metrics = computed(() => [
  {
    key: 'total_courses',
    label: 'Khóa học đã duyệt',
    value: displayValues.total_courses,
    suffix: '+',
    decimals: 0,
  },
  {
    key: 'total_students',
    label: 'Học viên đang học',
    value: displayValues.total_students,
    suffix: '+',
    decimals: 0,
  },
  {
    key: 'total_instructors',
    label: 'Giảng viên chuyên môn',
    value: displayValues.total_instructors,
    suffix: '+',
    decimals: 0,
  },
  {
    key: 'avg_rating',
    label: 'Đánh giá trung bình',
    value: displayValues.avg_rating,
    suffix: '/5',
    decimals: 1,
  },
])

const formatNumber = (value, decimals = 0) => {
  if (decimals > 0) {
    return Number(value).toLocaleString('vi-VN', {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals,
    })
  }

  return Math.round(Number(value)).toLocaleString('vi-VN')
}

const startCountUp = () => {
  if (hasAnimated.value || isLoading.value) return

  hasAnimated.value = true
  const startedAt = performance.now()
  const duration = 950

  const tick = (now) => {
    const progress = Math.min((now - startedAt) / duration, 1)
    const eased = 1 - Math.pow(1 - progress, 3)

    Object.keys(displayValues).forEach((key) => {
      displayValues[key] = Number(stats[key]) * eased
    })

    if (progress < 1) {
      animationFrame = requestAnimationFrame(tick)
      return
    }

    Object.keys(displayValues).forEach((key) => {
      displayValues[key] = Number(stats[key])
    })
  }

  animationFrame = requestAnimationFrame(tick)
}

watch([isVisible, isLoading], () => {
  if (isVisible.value) startCountUp()
})

onMounted(async () => {
  observer = new IntersectionObserver(
    ([entry]) => {
      isVisible.value = entry.isIntersecting
    },
    { threshold: 0.35 },
  )

  if (sectionRef.value) {
    observer.observe(sectionRef.value)
  }

  try {
    const response = await getPublicStats()
    Object.assign(stats, response.data.data)
  } catch (error) {
    errorMessage.value = error.response?.data?.message ?? 'Không thể tải thống kê hiện tại.'
  } finally {
    isLoading.value = false
    if (isVisible.value) startCountUp()
  }
})

onBeforeUnmount(() => {
  observer?.disconnect()
  if (animationFrame) cancelAnimationFrame(animationFrame)
})
</script>

<template>
  <section ref="sectionRef" class="w-full border-y border-surface-variant bg-surface-container-low py-lg font-body">
    <div class="mx-auto grid w-full min-w-0 max-w-container-max grid-cols-1 gap-md px-margin-mobile md:grid-cols-4 md:px-gutter">
      <article
        v-for="metric in metrics"
        :key="metric.key"
        class="flex w-full min-w-0 flex-col items-center rounded-xl border border-surface-variant bg-surface/70 p-md text-center"
      >
        <div v-if="isLoading" class="h-10 w-28 animate-pulse rounded bg-surface-container-highest"></div>
        <p v-else class="w-full min-w-0 font-display text-[34px] font-bold leading-tight text-primary">
          {{ formatNumber(metric.value, metric.decimals) }}{{ metric.suffix }}
        </p>
        <p class="mt-2 w-full min-w-0 text-body-sm text-on-surface-variant">{{ metric.label }}</p>
      </article>

      <p v-if="errorMessage" class="col-span-full w-full text-center text-body-sm text-error">{{ errorMessage }}</p>
    </div>
  </section>
</template>
