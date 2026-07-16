<script setup>
import { onMounted, ref } from 'vue'
import { getCategories } from '../services/api'

const skillCategories = ref([])

const columns = [
  { title: 'Certifications', links: ['AWS Certified', 'CompTIA Security+', 'Google Cloud', 'PMI PMP', 'Azure Dev'] },
  { title: 'Community', links: ['Forums', 'Discord', 'API Documentation', 'Open Source', 'Partnerships'] },
]

const fetchCategories = async () => {
  try {
    const response = await getCategories()
    skillCategories.value = response.data.data ?? []
  } catch {
    skillCategories.value = []
  }
}

onMounted(fetchCategories)
</script>

<template>
  <footer class="bg-surface-container-lowest border-t border-surface-variant w-full pt-xl pb-lg px-margin-mobile md:px-gutter font-body">
    <div class="max-w-container-max mx-auto">
      <div class="grid w-full grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-lg mb-xl">
        <div class="w-full min-w-0">
          <span class="font-display text-headline-sm font-bold text-on-surface mb-md block">EduMarket</span>
          <p class="w-full text-body-sm text-on-surface-variant mb-lg">
            High-end training for the modern engineering landscape. Zero fluff, just code. We help developers master the stacks that define the future.
          </p>
          <div class="flex gap-md">
            <a v-for="icon in ['terminal', 'public', 'code']" :key="icon" class="text-on-surface-variant hover:text-primary transition-colors" href="#">
              <span class="material-symbols-outlined">{{ icon }}</span>
            </a>
          </div>
        </div>
        <div class="min-w-0">
          <h4 class="font-display text-on-surface mb-md font-semibold">Explore Skills</h4>
          <ul class="flex flex-col gap-sm">
            <li v-for="category in skillCategories" :key="category.id ?? category.slug">
              <RouterLink :to="`/category/${category.slug}`" class="text-body-sm text-on-surface-variant hover:text-primary transition-colors">{{ category.name }}</RouterLink>
            </li>
          </ul>
        </div>
        <div v-for="column in columns" :key="column.title" class="min-w-0">
          <h4 class="font-display text-on-surface mb-md font-semibold">{{ column.title }}</h4>
          <ul class="flex flex-col gap-sm">
            <li v-for="link in column.links" :key="link"><a href="#" class="text-body-sm text-on-surface-variant hover:text-primary transition-colors">{{ link }}</a></li>
          </ul>
        </div>
      </div>
      <div class="pt-lg border-t border-surface-variant flex w-full min-w-0 flex-col md:flex-row justify-between items-center gap-md">
        <p class="w-full min-w-0 text-center font-mono text-label-mono text-on-surface-variant md:w-auto md:text-left">© 2024 EduMarket. Built for Developers. &lt; /&gt;</p>
        <div class="flex w-full min-w-0 flex-wrap justify-center gap-lg md:w-auto">
          <a v-for="link in ['Privacy Policy', 'Terms of Service', 'Cookie Settings']" :key="link" class="text-on-surface-variant hover:text-primary transition-colors font-mono text-label-mono" href="#">{{ link }}</a>
        </div>
      </div>
    </div>
  </footer>
</template>
