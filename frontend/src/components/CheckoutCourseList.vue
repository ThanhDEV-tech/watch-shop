<script setup>
import CheckoutCourseItem from './CheckoutCourseItem.vue'

defineProps({
  courses: { type: Array, required: true },
})
</script>

<template>
  <section class="w-full min-w-0 space-y-md">
    <CheckoutCourseItem v-for="course in courses" :key="course.id" :course="course" />
  </section>
</template>
