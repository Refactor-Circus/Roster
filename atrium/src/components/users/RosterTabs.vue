<script setup lang="ts" generic="K extends string">
/**
 * A page's tabs as links-in-place: a `UButton` each, the current one marked
 * `aria-current="page"`, choosing one emits `select` (the page keeps the tab
 * in its URL). `testid` prefixes each button's `data-testid`.
 */
defineProps<{ tabs: readonly { key: K, label: string }[], current: K, label: string, testid: string }>()

const emit = defineEmits<{ select: [key: K] }>()
</script>

<template>
  <nav class="flex flex-wrap gap-1 border-b border-default" :aria-label="label">
    <UButton
      v-for="tab in tabs"
      :key="tab.key"
      color="neutral"
      variant="link"
      class="-mb-px rounded-none border-b-2"
      :class="tab.key === current ? 'border-primary text-highlighted' : 'border-transparent text-muted'"
      :aria-current="tab.key === current ? 'page' : undefined"
      :data-testid="`${testid}-${tab.key}`"
      @click="emit('select', tab.key)"
    >
      {{ tab.label }}
    </UButton>
  </nav>
</template>
