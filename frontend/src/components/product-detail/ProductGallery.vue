<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { gsap } from 'gsap'
import { motionTokens } from '../../utils/motion'

const props = defineProps({
  images: {
    type: Array,
    default: () => [],
  },
  modelValue: {
    type: String,
    default: '',
  },
  productName: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['update:modelValue', 'image-error', 'select-image'])

const mainImageRef = ref(null)
let imageTween

const currentImageIndex = computed(() => props.images.indexOf(props.modelValue))

const selectImage = (image) => {
  if (!image || image === props.modelValue) return

  emit('update:modelValue', image)
  emit('select-image', image)
}

watch(() => props.modelValue, async () => {
  await nextTick()

  imageTween?.kill()

  if (!mainImageRef.value) return

  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    gsap.set(mainImageRef.value, { autoAlpha: 1, clearProps: 'visibility' })
    return
  }

  imageTween = gsap.fromTo(mainImageRef.value, {
    autoAlpha: 0.35,
  }, {
    autoAlpha: 1,
    duration: motionTokens.durationImage,
    ease: motionTokens.easeImage,
    overwrite: 'auto',
  })
})

onBeforeUnmount(() => {
  imageTween?.kill()
})
</script>

<template>
  <div class="flex w-full min-w-0 flex-col gap-9 md:gap-10">
    <div class="relative aspect-[4/5] w-full min-w-0 overflow-hidden bg-white lg:aspect-[5/6]">
      <p
        v-if="images.length"
        class="absolute left-4 top-4 z-10 w-fit bg-white/80 px-2 py-1 font-body text-[10px] font-medium uppercase tracking-[0.24em] text-primary/35"
      >
        View {{ String(Math.max(currentImageIndex + 1, 1)).padStart(2, '0') }} / {{ String(images.length).padStart(2, '0') }}
      </p>
      <img
        v-if="modelValue"
        ref="mainImageRef"
        :src="modelValue"
        :alt="productName"
        class="h-full w-full scale-110 object-contain"
        @error="emit('image-error')"
      />
      <div v-else class="flex h-full w-full items-center justify-center font-body text-[11px] font-medium uppercase tracking-[0.24em] text-primary/35">
        Image unavailable
      </div>
    </div>

    <div
      v-if="images.length > 1"
      class="grid w-full min-w-0 grid-cols-2 gap-5 md:gap-8"
      aria-label="Product image thumbnails"
    >
      <button
        v-for="image in images"
        :key="image"
        type="button"
        class="aspect-square w-full min-w-0 overflow-hidden bg-white transition-opacity hover:opacity-80 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 motion-reduce:transition-none"
        :class="image === modelValue ? 'opacity-100' : 'opacity-60'"
        :aria-label="`View ${productName} image ${images.indexOf(image) + 1}`"
        :aria-current="image === modelValue ? 'true' : undefined"
        @click="selectImage(image)"
      >
        <img
          :src="image"
          :alt="productName"
          class="h-full w-full object-cover transition-transform duration-[1600ms] hover:scale-105 motion-reduce:transition-none"
          loading="lazy"
        />
      </button>
    </div>
  </div>
</template>
