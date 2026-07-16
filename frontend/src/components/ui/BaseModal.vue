<script setup>
const props = defineProps({
  maxWidth: {
    type: String,
    default: 'lg',
    validator: (value) => ['sm', 'md', 'lg', 'xl', 'wide'].includes(value),
  },
  ariaLabelledby: { type: String, default: undefined },
  closeOnBackdrop: { type: Boolean, default: true },
  backdropBlur: { type: Boolean, default: false },
})

const emit = defineEmits(['close'])

// Use explicit rem values because this project defines --spacing-md/lg/xl.
// Tailwind's max-w-lg would otherwise resolve to the 48px spacing token.
const widthClasses = {
  sm: 'max-w-[24rem]',
  md: 'max-w-[28rem]',
  lg: 'max-w-[32rem]',
  xl: 'max-w-[36rem]',
  wide: 'max-w-[64rem]',
}

const closeFromBackdrop = () => {
  if (props.closeOnBackdrop) emit('close')
}
</script>

<template>
  <Teleport to="body">
    <div
      class="fixed inset-0 z-[100] flex w-full min-w-0 items-center justify-center overflow-y-auto bg-black/70 p-margin-mobile"
      :class="{ 'backdrop-blur-sm': backdropBlur }"
      role="presentation"
      @click.self="closeFromBackdrop"
    >
      <div
        class="w-full min-w-0"
        :class="widthClasses[maxWidth]"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="ariaLabelledby"
      >
        <slot />
      </div>
    </div>
  </Teleport>
</template>
