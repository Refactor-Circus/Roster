<script setup lang="ts">
import type { RosterProfileForm } from './users'

/**
 * The profile fields of the create form and the user page, as v1's
 * `profile-fields` partial: display name, avatar URL, time zone, locale and
 * bio, each with its 422 error.
 */
defineProps<{ errors: Record<string, string[]> }>()

const form = defineModel<RosterProfileForm>({ required: true })

const { t } = useI18n()

const fields = ['display_name', 'avatar_url', 'timezone', 'locale'] as const
</script>

<template>
  <UFormField
    v-for="field in fields"
    :key="field"
    :label="t(`roster.users.profile.${field}`)"
    :error="errors[field]?.[0]"
  >
    <UInput v-model="form[field]" :type="field === 'avatar_url' ? 'url' : 'text'" class="w-full" :data-testid="`roster-profile-${field}`" />
  </UFormField>
  <UFormField :label="t('roster.users.profile.bio')" :error="errors.bio?.[0]">
    <UTextarea v-model="form.bio" class="w-full" data-testid="roster-profile-bio" />
  </UFormField>
</template>
