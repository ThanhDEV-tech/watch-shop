<script setup>
import { computed, useId } from 'vue'

const props = defineProps({
  content: {
    type: Object,
    default: null,
  },
})

const headingId = `editorial-signature-${useId()}`

const readString = (value) => (typeof value === 'string' ? value.trim() : '')
const hasText = (value) => readString(value).length > 0

const isEnabled = computed(() => props.content?.enabled === true)
const desktopImage = computed(() => readString(props.content?.desktopImage))
const mobileImage = computed(() => readString(props.content?.mobileImage))
const imageSrc = computed(() => desktopImage.value || mobileImage.value)
const imageAlt = computed(() => readString(props.content?.alt))
const objectPosition = computed(() => readString(props.content?.focalPoint) || 'center')

const eyebrow = computed(() => readString(props.content?.eyebrow))
const heading = computed(() => readString(props.content?.heading))
const bodyParagraphs = computed(() =>
  readString(props.content?.body)
    .split(/\n{2,}/)
    .map((paragraph) => paragraph.trim())
    .filter(Boolean),
)
const caption = computed(() => readString(props.content?.caption))

const hasHeading = computed(() => hasText(heading.value))
const hasOverlayText = computed(
  () => hasText(eyebrow.value) || hasHeading.value || bodyParagraphs.value.length > 0,
)
const hasCaption = computed(() => hasText(caption.value))
const shouldRender = computed(() => isEnabled.value && hasText(imageSrc.value))
const isLightTheme = computed(() => props.content?.theme === 'light')

const sectionClass = computed(() =>
  isLightTheme.value
    ? 'bg-background text-primary'
    : 'bg-[var(--watch-color-ink-950)] text-[var(--watch-color-ivory-50)]',
)
const eyebrowClass = computed(() =>
  isLightTheme.value
    ? 'text-accent-primary'
    : 'text-[var(--watch-color-gold-300)]',
)
const bodyClass = computed(() =>
  isLightTheme.value
    ? 'text-secondary'
    : 'text-[var(--watch-color-ivory-100)]/85',
)
const captionClass = computed(() =>
  isLightTheme.value
    ? 'text-muted'
    : 'text-[var(--watch-color-ivory-100)]/60',
)
</script>

<template>
  <section
    v-if="shouldRender"
    class="w-full min-w-0 overflow-hidden"
    :class="sectionClass"
    :aria-labelledby="hasHeading ? headingId : undefined"
  >
    <div class="mx-auto w-full max-w-[1720px] px-margin-mobile py-14 md:px-gutter md:py-18 lg:py-20">
      <div class="relative mx-auto w-full min-w-0 md:w-[90%]">
        <div class="relative aspect-[4/5] w-full min-w-0 overflow-hidden md:aspect-[16/9] lg:aspect-[21/10]">
          <picture>
            <source
              v-if="mobileImage"
              media="(max-width: 767px)"
              :srcset="mobileImage"
            >
            <img
              class="h-full w-full object-cover"
              :src="imageSrc"
              :alt="imageAlt"
              :style="{ objectPosition }"
              loading="lazy"
              decoding="async"
            >
          </picture>
        </div>

        <div
          v-if="hasOverlayText"
          class="w-full min-w-0 px-0 pt-6 md:absolute md:bottom-0 md:left-0 md:max-w-3xl md:px-9 md:pb-9 md:pt-0 lg:max-w-4xl lg:px-12 lg:pb-12"
        >
          <p
            v-if="eyebrow"
            class="mb-4 w-full min-w-0 font-body text-xs font-semibold uppercase tracking-[0.28em]"
            :class="eyebrowClass"
          >
            {{ eyebrow }}
          </p>
          <h2
            v-if="heading"
            :id="headingId"
            class="w-full min-w-0 max-w-3xl font-display text-4xl font-semibold leading-[1.04] tracking-normal md:text-6xl lg:text-7xl"
          >
            {{ heading }}
          </h2>
          <div
            v-if="bodyParagraphs.length"
            class="mt-4 w-full min-w-0 max-w-xl space-y-3 font-body text-sm leading-7 md:text-base"
            :class="bodyClass"
          >
            <p
              v-for="paragraph in bodyParagraphs"
              :key="paragraph"
              class="w-full min-w-0"
            >
              {{ paragraph }}
            </p>
          </div>
        </div>

        <p
          v-if="hasCaption"
          class="mt-3 w-full min-w-0 font-body text-xs leading-6 md:absolute md:bottom-4 md:right-5 md:mt-0 md:max-w-xs md:text-right"
          :class="captionClass"
        >
          {{ caption }}
        </p>
      </div>
    </div>
  </section>
</template>
