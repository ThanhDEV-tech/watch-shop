<script setup>
defineProps({
  product: {
    type: Object,
    required: true,
  },
  genderLabel: {
    type: String,
    default: '',
  },
  displayPrice: {
    type: String,
    default: '',
  },
  options: {
    type: Object,
    required: true,
  },
  selected: {
    type: Object,
    required: true,
  },
  selectedVariant: {
    type: Object,
    default: null,
  },
  stockQuantity: {
    type: Number,
    default: 0,
  },
  quantity: {
    type: [Number, String],
    default: 1,
  },
  quantityMax: {
    type: Number,
    default: 1,
  },
  canAddToCart: {
    type: Boolean,
    default: false,
  },
  isAdding: {
    type: Boolean,
    default: false,
  },
  isOutOfStock: {
    type: Boolean,
    default: false,
  },
  hasInvalidCompleteSelection: {
    type: Boolean,
    default: false,
  },
  cartMessage: {
    type: String,
    default: '',
  },
  cartError: {
    type: String,
    default: '',
  },
  colorMap: {
    type: Object,
    required: true,
  },
  isOptionDisabled: {
    type: Function,
    required: true,
  },
  movementLabel: {
    type: Function,
    required: true,
  },
})

const emit = defineEmits(['select-option', 'add-quantity', 'update:quantity', 'clamp-quantity', 'add-to-cart'])

const normalizeNumberInput = (value) => {
  const parsed = Number.parseFloat(value)

  return Number.isNaN(parsed) ? value : parsed
}
</script>

<template>
  <div class="flex w-full min-w-0 flex-col md:sticky md:top-28 md:h-fit">
    <div class="w-full min-w-0">
      <p class="w-full font-body text-[11px] font-medium uppercase tracking-[0.3em] text-secondary/70">
        {{ product.brand?.name ?? 'Watchora' }}
      </p>
      <h1 class="mt-5 w-full max-w-[12ch] break-words font-display text-[clamp(3rem,12vw,4.8rem)] font-normal leading-[0.95] tracking-normal text-primary md:text-[clamp(3.35rem,4.8vw,5.1rem)]">
        {{ product.name }}
      </h1>

      <div class="mt-5 flex w-full min-w-0 flex-wrap items-center gap-x-3 gap-y-2 font-body text-[10px] font-medium uppercase tracking-[0.18em] text-secondary/72">
        <span v-if="product.category?.name">
          {{ product.category.name }}
        </span>
        <span v-if="product.category?.name && genderLabel" class="h-px w-6 bg-primary/18"></span>
        <span v-if="genderLabel">
          {{ genderLabel }}
        </span>
        <span v-if="isOutOfStock" class="border-l border-primary/15 pl-3 text-[var(--accent-danger)]">
          Hết hàng
        </span>
      </div>

      <p class="mt-6 w-full max-w-[31rem] font-body text-base font-light leading-8 text-secondary md:text-[17px] md:leading-9">
        {{ product.description }}
      </p>
    </div>

    <div class="mt-11 w-full min-w-0 border-t border-primary/10 pt-9">
      <p class="w-full font-body text-[11px] font-medium uppercase tracking-[0.2em] text-secondary/70">
        Select Execution
      </p>
      <div class="mt-7 flex w-full min-w-0 flex-wrap gap-x-8 gap-y-6">
        <button
          v-for="value in options.strap_color"
          :key="`strap-${value}`"
          type="button"
          class="group flex cursor-pointer flex-col items-center gap-3 transition-opacity disabled:cursor-not-allowed disabled:opacity-25 motion-reduce:transition-none"
          :class="selected.strap_color === value ? 'opacity-100' : 'opacity-45 hover:opacity-100'"
          :disabled="isOptionDisabled('strap_color', value)"
          @click="emit('select-option', 'strap_color', value, $event)"
        >
          <span
            class="h-12 w-12 rounded-full border transition-colors"
            :class="selected.strap_color === value ? 'border-primary' : 'border-transparent group-hover:border-primary/40'"
            :style="{ backgroundColor: colorMap[value] ?? '#d6d3d1' }"
          ></span>
          <span class="font-body text-[10px] font-medium uppercase tracking-widest text-primary">
            {{ value }}
          </span>
        </button>
      </div>
    </div>

    <div v-if="options.dial_color.length > 1" class="mt-9 w-full min-w-0">
      <p class="w-full font-body text-[11px] font-medium uppercase tracking-[0.2em] text-secondary/70">
        Dial
      </p>
      <div class="mt-4 flex w-full min-w-0 flex-wrap gap-3">
        <button
          v-for="value in options.dial_color"
          :key="`dial-${value}`"
          type="button"
          class="min-h-10 cursor-pointer border px-4 py-2 font-body text-[11px] font-medium uppercase tracking-[0.16em] transition-colors disabled:cursor-not-allowed disabled:opacity-25 motion-reduce:transition-none"
          :class="selected.dial_color === value ? 'border-primary bg-primary text-on-primary' : 'border-primary/15 bg-transparent text-primary hover:border-primary/45'"
          :disabled="isOptionDisabled('dial_color', value)"
          @click="emit('select-option', 'dial_color', value, $event)"
        >
          {{ value }}
        </button>
      </div>
    </div>

    <div class="mt-8 grid w-full min-w-0 gap-5 sm:grid-cols-2">
      <div class="w-full min-w-0">
        <p class="w-full font-body text-[11px] font-medium uppercase tracking-[0.2em] text-secondary/70">
          Diameter
        </p>
        <div class="mt-4 flex w-full min-w-0 flex-wrap gap-3">
          <button
            v-for="value in options.diameter_mm"
            :key="value"
            type="button"
            class="min-h-10 cursor-pointer border px-4 py-2 font-body text-[11px] font-medium uppercase tracking-[0.16em] transition-colors disabled:cursor-not-allowed disabled:opacity-25 motion-reduce:transition-none"
            :class="Number(selected.diameter_mm) === Number(value) ? 'border-primary bg-primary text-on-primary' : 'border-primary/15 bg-transparent text-primary hover:border-primary/45'"
            :disabled="isOptionDisabled('diameter_mm', value)"
            @click="emit('select-option', 'diameter_mm', value, $event)"
          >
            {{ value }}mm
          </button>
        </div>
      </div>

      <div class="w-full min-w-0">
        <p class="w-full font-body text-[11px] font-medium uppercase tracking-[0.2em] text-secondary/70">
          Movement
        </p>
        <div class="mt-4 flex w-full min-w-0 flex-wrap gap-3">
          <button
            v-for="value in options.movement_type"
            :key="value"
            type="button"
            class="min-h-10 cursor-pointer border px-4 py-2 font-body text-[11px] font-medium uppercase tracking-[0.16em] transition-colors disabled:cursor-not-allowed disabled:opacity-25 motion-reduce:transition-none"
            :class="selected.movement_type === value ? 'border-primary bg-primary text-on-primary' : 'border-primary/15 bg-transparent text-primary hover:border-primary/45'"
            :disabled="isOptionDisabled('movement_type', value)"
            @click="emit('select-option', 'movement_type', value, $event)"
          >
            {{ movementLabel(value) }}
          </button>
        </div>
      </div>
    </div>

    <p v-if="hasInvalidCompleteSelection" class="mt-6 w-full border-l border-[var(--accent-danger)] px-4 py-2 text-sm font-semibold text-[var(--accent-danger)]">
      Tổ hợp này hiện không có sẵn.
    </p>

    <div class="mt-11 w-full min-w-0 border-t border-primary/10 pt-8">
      <p class="w-full font-display text-[clamp(2.35rem,5vw,3.15rem)] font-normal leading-none text-primary">
        {{ displayPrice }}
      </p>
      <p v-if="selectedVariant" class="mt-5 w-full font-body text-[11px] font-medium uppercase tracking-[0.18em] text-secondary/72">
        Stock {{ stockQuantity }} · SKU {{ selectedVariant.sku }}
      </p>
      <p v-else class="mt-5 w-full font-body text-[11px] font-medium uppercase tracking-[0.18em] text-secondary/72">
        Complete the selection to view stock.
      </p>
    </div>

    <div class="mt-7 flex w-full min-w-0 flex-col gap-4">
      <div class="flex h-14 w-full min-w-0 items-center border border-primary/20 bg-transparent">
        <button
          type="button"
          class="h-full w-12 shrink-0 cursor-pointer text-xl font-semibold transition-colors hover:bg-primary/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/25 disabled:cursor-not-allowed disabled:opacity-40 motion-reduce:transition-none"
          :disabled="quantity <= 1"
          @click="emit('add-quantity', -1)"
        >
          -
        </button>
        <input
          :value="quantity"
          type="number"
          min="1"
          :max="quantityMax"
          class="h-full w-full min-w-0 border-x border-primary/15 bg-transparent text-center font-semibold text-primary outline-none focus:bg-primary/5"
          @input="emit('update:quantity', normalizeNumberInput($event.target.value))"
          @blur="emit('clamp-quantity')"
        />
        <button
          type="button"
          class="h-full w-12 shrink-0 cursor-pointer text-xl font-semibold transition-colors hover:bg-primary/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/25 disabled:cursor-not-allowed disabled:opacity-40 motion-reduce:transition-none"
          :disabled="quantity >= stockQuantity"
          @click="emit('add-quantity', 1)"
        >
          +
        </button>
      </div>

      <button
        type="button"
        class="min-h-16 w-full cursor-pointer bg-primary px-8 font-body text-sm font-medium uppercase tracking-[0.22em] text-on-primary transition-opacity hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/35 disabled:cursor-not-allowed disabled:opacity-35 motion-reduce:transition-none"
        :disabled="!canAddToCart"
        @click="emit('add-to-cart')"
      >
        {{ isAdding ? 'Đang thêm...' : isOutOfStock ? 'Hết hàng' : 'Thêm vào giỏ hàng' }}
      </button>
    </div>

    <p v-if="cartMessage" class="mt-3 w-full text-sm font-semibold text-[var(--accent-success)]">{{ cartMessage }}</p>
    <p v-if="cartError" class="mt-3 w-full text-sm font-semibold text-[var(--accent-danger)]">{{ cartError }}</p>
  </div>
</template>
