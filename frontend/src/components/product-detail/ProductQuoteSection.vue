<script setup>
defineProps({
  quotes: {
    type: Array,
    default: () => [],
  },
  canReview: {
    type: Boolean,
    default: false,
  },
  rating: {
    type: Number,
    default: 5,
  },
  comment: {
    type: String,
    default: '',
  },
  existingReview: {
    type: Object,
    default: null,
  },
  isSubmitting: {
    type: Boolean,
    default: false,
  },
  message: {
    type: String,
    default: '',
  },
  error: {
    type: String,
    default: '',
  },
})

defineEmits(['submit-review', 'update:rating', 'update:comment'])
</script>

<template>
  <section class="bg-surface-container px-6 py-24 md:px-20 md:py-40 lg:px-[100px]">
    <div class="mx-auto grid w-full max-w-[1440px] grid-cols-1 gap-16 md:grid-cols-2 md:gap-32">
      <div
        v-for="(quote, index) in quotes"
        :key="quote.key"
        class="w-full min-w-0 space-y-10"
        :class="index === 1 ? 'md:mt-40' : ''"
      >
        <p class="w-full font-display text-[clamp(2rem,4vw,3rem)] font-normal italic leading-tight text-primary">
          "{{ quote.quote }}"
        </p>
        <div class="flex w-full min-w-0 items-center gap-6">
          <span class="h-px w-16 bg-primary/30"></span>
          <span class="font-body text-[11px] font-medium uppercase tracking-[0.3em] text-secondary">
            {{ quote.author }}
          </span>
        </div>
      </div>

      <div class="w-full min-w-0 md:col-span-2">
        <form
          v-if="canReview"
          id="product-review-form"
          class="mt-12 flex w-full min-w-0 flex-col border-t border-primary/15 pt-8"
          @submit.prevent="$emit('submit-review')"
        >
          <label class="flex w-full min-w-0 flex-col gap-2 font-body text-[11px] font-medium uppercase tracking-[0.18em] text-secondary">
            Rating
            <select
              :value="rating"
              class="h-12 w-full border border-primary/15 bg-transparent px-3 text-base normal-case tracking-normal text-primary outline-none focus:border-primary"
              required
              @change="$emit('update:rating', Number($event.target.value))"
            >
              <option v-for="score in [5, 4, 3, 2, 1]" :key="score" :value="score">
                {{ score }} sao
              </option>
            </select>
          </label>

          <label class="mt-5 flex w-full min-w-0 flex-col gap-2 font-body text-[11px] font-medium uppercase tracking-[0.18em] text-secondary">
            Comment
            <textarea
              :value="comment"
              rows="4"
              maxlength="2000"
              class="w-full border border-primary/15 bg-transparent px-4 py-3 text-base leading-8 text-primary outline-none focus:border-primary"
              placeholder="Chia sẻ cảm nhận về thiết kế, cảm giác đeo, độ hoàn thiện..."
              @input="$emit('update:comment', $event.target.value.trim())"
            ></textarea>
          </label>

          <button
            type="submit"
            class="mt-6 min-h-12 w-full cursor-pointer bg-primary px-5 font-body text-sm font-medium uppercase tracking-[0.16em] text-on-primary transition-opacity hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40 sm:w-fit"
            :disabled="isSubmitting"
          >
            {{ isSubmitting ? 'Đang gửi...' : existingReview ? 'Cập nhật đánh giá' : 'Gửi đánh giá' }}
          </button>

          <p v-if="message" class="mt-4 w-full border-l border-[var(--accent-success)] pl-4 text-sm font-semibold text-[var(--accent-success)]">{{ message }}</p>
          <p v-if="error" class="mt-4 w-full border-l border-[var(--accent-danger)] pl-4 text-sm font-semibold text-[var(--accent-danger)]">{{ error }}</p>
        </form>
      </div>
    </div>
  </section>
</template>
