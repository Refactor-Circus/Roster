<script setup lang="ts">
import type { RosterAction } from './users'

/**
 * A failed mutation that is not a validation error: the no-access notice on
 * a 403, else Roster's message.
 */
defineProps<{ action: RosterAction }>()

const { t } = useI18n()
</script>

<template>
  <AtriumNoAccess v-if="action.forbidden.value" compact :title="t('roster.users.state.action_forbidden')" />
  <UAlert v-else-if="action.message.value" color="error" variant="subtle" :title="action.message.value" data-testid="roster-action-error" />
</template>
