import productEditorialContent from '../config/productEditorialContent.js'

const SECTION_NAMES = ['hero', 'story', 'editorialImage', 'craft', 'signatureMoment']

const SECTION_DEFAULTS = {
  hero: {
    enabled: true,
    desktopImage: '',
    mobileImage: '',
    alt: '',
    focalPoint: 'center',
    theme: 'light',
    motionPreset: 'hero-sequence',
    fallback: 'product-gallery',
  },
  story: {
    enabled: true,
    eyebrow: '',
    heading: '',
    body: '',
    theme: 'light',
    motionPreset: 'text-stagger',
    fallback: 'product-content',
  },
  editorialImage: {
    enabled: false,
    desktopImage: '',
    mobileImage: '',
    alt: '',
    caption: '',
    focalPoint: 'center',
    imagePosition: 'center',
    theme: 'light',
    motionPreset: 'image-clip',
    fallback: 'hide',
  },
  craft: {
    enabled: false,
    desktopImage: '',
    mobileImage: '',
    alt: '',
    eyebrow: '',
    heading: '',
    body: '',
    imagePosition: 'left',
    theme: 'light',
    motionPreset: 'image-clip',
    fallback: 'hide',
  },
  signatureMoment: {
    enabled: false,
    desktopImage: '',
    mobileImage: '',
    alt: '',
    caption: '',
    focalPoint: 'center',
    theme: 'dark',
    motionPreset: 'subtle-parallax',
    fallback: 'hide',
  },
}

const resolveSlug = (productOrSlug) => {
  if (typeof productOrSlug === 'string') return productOrSlug.trim()

  return String(productOrSlug?.slug ?? '').trim()
}

const readString = (value) => String(value ?? '').trim()

const stripHtml = (value) => readString(value).replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim()

const productContentFallback = (productOrSlug) => {
  if (!productOrSlug || typeof productOrSlug === 'string') return ''

  return stripHtml(productOrSlug.content) || stripHtml(productOrSlug.description)
}

const normalizeSection = (sectionName, section = {}) => {
  const defaults = SECTION_DEFAULTS[sectionName]
  const sectionConfig = section && typeof section === 'object' ? section : {}
  const merged = {
    ...defaults,
    ...sectionConfig,
  }

  if ('enabled' in sectionConfig) {
    merged.enabled = Boolean(sectionConfig.enabled)
  }

  if ('desktopImage' in defaults || 'mobileImage' in defaults) {
    merged.desktopImage = String(merged.desktopImage ?? '')
    merged.mobileImage = String(merged.mobileImage || merged.desktopImage || '')
  }

  return { ...merged }
}

const applyProductFallbacks = (editorial, productOrSlug) => {
  const productStory = productContentFallback(productOrSlug)

  if (!productStory || editorial.story.fallback !== 'product-content' || readString(editorial.story.body)) {
    return editorial
  }

  return {
    ...editorial,
    story: {
      ...editorial.story,
      body: productStory,
    },
  }
}

/**
 * Resolve normalized editorial content for a product object or product slug.
 * Missing slugs fall back to Product API data for hero/story and hide extra sections.
 */
export const resolveProductEditorial = (productOrSlug) => {
  const slug = resolveSlug(productOrSlug)
  const manifestEntry = productEditorialContent[slug] ?? {}

  const editorial = SECTION_NAMES.reduce((resolvedEditorial, sectionName) => ({
    ...resolvedEditorial,
    [sectionName]: normalizeSection(sectionName, manifestEntry[sectionName]),
  }), {})

  return applyProductFallbacks(editorial, productOrSlug)
}

export const productEditorialSections = SECTION_NAMES
export const productEditorialDefaults = SECTION_DEFAULTS

export default resolveProductEditorial
