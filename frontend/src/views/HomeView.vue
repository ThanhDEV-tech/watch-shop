<script setup>
import { ref } from 'vue'
import CategoryChips from '../components/CategoryChips.vue'
import CertificationBanner from '../components/CertificationBanner.vue'
import CourseGrid from '../components/CourseGrid.vue'
import FaqSection from '../components/FaqSection.vue'
import FeaturedCategories from '../components/FeaturedCategories.vue'
import FinalCta from '../components/FinalCta.vue'
import Hero from '../components/Hero.vue'
import HowItWorks from '../components/HowItWorks.vue'
import InstructorSpotlight from '../components/InstructorSpotlight.vue'
import StatsBar from '../components/StatsBar.vue'
import Testimonials from '../components/Testimonials.vue'
import TrustedBy from '../components/TrustedBy.vue'

const activeCategoryId = ref(null)
</script>

<template>
  <main class="w-full min-w-0">
    <Hero />
    <StatsBar />
    <CategoryChips @select="activeCategoryId = $event" />
    <FeaturedCategories />
    <CourseGrid :category-id="activeCategoryId" />
    <TrustedBy />
    <InstructorSpotlight />
    <Testimonials />
    <CertificationBanner />
    <FaqSection />
    <HowItWorks />
    <FinalCta />
  </main>
</template>
