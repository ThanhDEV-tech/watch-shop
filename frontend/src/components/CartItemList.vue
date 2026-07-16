<script setup>
import CartItem from './CartItem.vue'

defineProps({
  items: { type: Array, required: true },
})

defineEmits(['remove'])
</script>

<template>
  <section class="w-full min-w-0 space-y-md">
    <CartItem v-for="item in items" :key="item.id" :item="item" @remove="$emit('remove', $event)" />
  </section>
</template>
