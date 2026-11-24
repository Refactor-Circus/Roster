<script setup lang="ts">
import { transferStatusColor } from './transfers'

/** A transfer's status, as v1's status dot showed it. */
const props = defineProps<{ status: string, testid?: string }>()

const { t } = useI18n()
</script>

<template>
  <UBadge :color="transferStatusColor(props.status)" variant="subtle" :data-testid="props.testid">{{ t(`roster.transfers.statuses.${props.status}`) }}</UBadge>
</template>
