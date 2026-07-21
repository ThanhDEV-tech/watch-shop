<script setup>
import { computed, useId } from 'vue'

const props = defineProps({
  content: {
    type: Object,
    default: null,
  },
})

const headingId = `editorial-story-${useId()}`

const readString = (value) => String(value ?? '').trim()
const hasText = (value) => Boolean(readString(value))

const bodyParagraphs = computed(() => readString(props.content?.body)
  .split(/\n{2,}/)
  .map((paragraph) => paragraph.trim())
  .filter(Boolean))

const imageSrc = computed(() => readString(props.content?.desktopImage) || readString(props.content?.mobileImage))
const hasImage = computed(() => hasText(imageSrc.value))
const hasTextContent = computed(() => (
  hasText(props.content?.eyebrow)
  || hasText(props.content?.heading)
  || bodyParagraphs.value.length > 0
))

const isEnabled = computed(() => props.content?.enabled === true)
const shouldRender = computed(() => isEnabled.value && (hasTextContent.value || hasImage.value))
const hasSplitLayout = computed(() => hasTextContent.value && hasImage.value)
const imageIsLeft = computed(() => props.content?.imagePosition === 'left')
const isDarkTheme = computed(() => props.content?.theme === 'dark')

const sectionClasses = computed(() => [
  isDarkTheme.value
    ? 'bg-[var(--watch-color-ink-900)] text-[var(--watch-color-ivory-50)]'
    : 'bg-background text-primary',
])

const layoutClasses = computed(() => [
  hasSplitLayout.value
    ? 'lg:grid-cols-[minmax(0,0.58fr)_minmax(280px,0.34fr)] lg:items-start lg:justify-between'
    : 'lg:grid-cols-1',
])

const textClasses = computed(() => [
  'w-full min-w-0',
  hasSplitLayout.value && imageIsLeft.value ? 'lg:order-2' : 'lg:order-1',
])

const imageClasses = computed(() => [
  'w-full min-w-0 lg:pt-10 xl:pt-14',
  imageIsLeft.value ? 'order-first lg:order-1' : 'order-last lg:order-2',
])

const eyebrowClasses = computed(() => [
  'w-full text-[11px] font-bold uppercase tracking-[0.32em]',
  isDarkTheme.value ? 'text-[var(--watch-color-gold-300)]' : 'text-primary/45',
])

const headingClasses = computed(() => [
  'mt-5 w-full max-w-xl font-display text-[clamp(2.65rem,8vw,4.9rem)] font-semibold leading-[0.96]',
  isDarkTheme.value ? 'text-[var(--watch-color-ivory-50)]' : 'text-primary',
])

const bodyClasses = computed(() => [
  'w-full max-w-[64ch] text-base leading-8 md:text-lg md:leading-9',
  isDarkTheme.value ? 'text-[var(--watch-color-ivory-100)]' : 'text-primary/64',
])

const imageFrameClasses = computed(() => [
  'relative aspect-[4/3] w-full overflow-hidden border',
  isDarkTheme.value ? 'bg-white/5' : 'bg-background',
  isDarkTheme.value ? 'border-white/10' : 'border-primary/10',
  hasSplitLayout.value ? 'md:aspect-[5/4] lg:aspect-[4/5]' : 'md:aspect-[16/9]',
])

const objectPosition = computed(() => readString(props.content?.focalPoint) || 'center')
const mobileImage = computed(() => readString(props.content?.mobileImage))
const imageAlt = computed(() => readString(props.content?.alt) || readString(props.content?.heading) || '')
const caption = computed(() => readString(props.content?.caption))
const captionClasses = computed(() => [
  'mt-3 w-full font-body text-[11px] font-medium uppercase tracking-[0.22em]',
  isDarkTheme.value ? 'text-[var(--watch-color-ivory-100)]/55' : 'text-primary/42',
])
</script>

<template>
  <section
    v-if="shouldRender"
    class="w-full min-w-0"
    :class="sectionClasses"
    :aria-labelledby="hasText(content?.heading) ? headingId : undefined"
  >
    <div
      class="mx-auto grid w-full max-w-[1560px] gap-12 px-margin-mobile py-20 md:px-gutter md:py-28 lg:gap-16 xl:gap-20"
      :class="layoutClasses"
    >
      <div
        v-if="hasTextContent"
        :class="textClasses"
      >
        <p
          v-if="hasText(content?.eyebrow)"
          :class="eyebrowClasses"
        >
          {{ content.eyebrow }}
        </p>

        <h2
          v-if="hasText(content?.heading)"
          :id="headingId"
          :class="headingClasses"
        >
          {{ content.heading }}
        </h2>

        <div
          v-if="bodyParagraphs.length"
          class="mt-7 flex w-full min-w-0 flex-col gap-5 md:mt-8"
        >
          <p
            v-for="paragraph in bodyParagraphs"
            :key="paragraph"
            :class="bodyClasses"
          >
            {{ paragraph }}
          </p>
        </div>
      </div>

      <div
        v-if="hasImage"
        :class="imageClasses"
      >
        <div :class="imageFrameClasses">
          <picture>
            <source
              v-if="mobileImage && mobileImage !== imageSrc"
              :srcset="mobileImage"
              media="(max-width: 767px)"
            />
            <img
              :src="imageSrc"
              :alt="imageAlt"
              class="h-full w-full object-cover"
              :style="{ objectPosition }"
              loading="lazy"
              decoding="async"
            />
          </picture>
        </div>
        <p
          v-if="caption"
          :class="captionClasses"
        >
          {{ caption }}
        </p>
      </div>
    </div>
  </section>
</template>
