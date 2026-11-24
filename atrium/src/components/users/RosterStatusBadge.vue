<script setup lang="ts">
import { statusColor } from './users'

/** A user's status, as a badge (v1's status dot). */
defineProps<{ status: string }>()

const { t, te } = useI18n()
</script>

<template>
  <UBadge :color="statusColor(status)" variant="subtle" :data-status="status">
    {{ te(`roster.users.statuses.${status}`) ? t(`roster.users.statuses.${status}`) : status }}
  </UBadge>
</template>
