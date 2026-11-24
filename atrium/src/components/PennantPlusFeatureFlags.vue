<script setup lang="ts">
import type { SelectItem, TableColumn } from '@nuxt/ui'
import PennantPlusSetValue from './PennantPlusSetValue.vue'
import {
  displayValue,
  loosely,
  pennantPlusData,
  usePennantPlus,
  usePennantPlusAction,
  type PennantPlusClient,
  type PennantPlusFeature,
  type PennantPlusList,
  type PennantPlusScopeType,
  type PennantPlusValue
} from './pennantplus'

/**
 * The body of `pennantplus.features`, ported from PennantPlus v1's
 * `atrium/index.blade.php`: the stored feature flag values, filtered by
 * feature, scope and scope id and paged as `values.index` pages, with v1's
 * changes:
 *
 * - set a value (<PennantPlusSetValue>, `values.update`),
 * - switch a stored value on or off from its row (`values.update`),
 * - forget a stored value (`values.destroy`), after a confirmation,
 * - purge the feature the list is filtered to (`features.destroy`), after
 *   v1's confirmation.
 *
 * v1 showed the screen only to users who manage feature flags
 * (`pennantplus.ability`); PennantPlus's API now answers them 403, which
 * hides every control and shows <AtriumNoAccess>.
 */
interface Filters {
  feature: string
  scope: string
  scope_id: string
}

const FEATURE_LIST = 'pennantplus-feature-options'

/** The scope filter's "all scopes" item: a select item cannot have an empty value. */
const ALL_SCOPES = '*'

const { t, locale } = useI18n()
const client = usePennantPlus()
const action = usePennantPlusAction()

const features = shallowRef<PennantPlusFeature[]>([])
const scopeTypes = shallowRef<PennantPlusScopeType[]>([])
const values = shallowRef<PennantPlusValue[]>([])
const page = ref(1)
const lastPage = ref(1)
/** The first load has answered: later loads keep the controls in place. */
const loaded = ref(false)
const failed = ref(false)
const refused = ref(false)
const status = ref<string | null>(null)

const filters = reactive<Filters>({ feature: '', scope: '', scope_id: '' })
/** The filters the list shows: the purge offer follows these, not what is typed. */
const applied = ref<Filters>({ feature: '', scope: '', scope_id: '' })

const forbidden = computed(() => refused.value || action.forbidden.value)

/** The scope filter's value, with no scope as ALL_SCOPES. */
const scopeFilter = computed({
  get: () => filters.scope === '' ? ALL_SCOPES : filters.scope,
  set: (scope: string) => { filters.scope = scope === ALL_SCOPES ? '' : scope }
})

const scopeItems = computed<SelectItem[]>(() => [
  { label: t('pennantplus.features.all_scopes'), value: ALL_SCOPES },
  ...scopeTypes.value.map(scope => ({ label: scope.label, value: scope.type }))
])

const columns = computed<TableColumn<PennantPlusValue>[]>(() => [
  { accessorKey: 'feature', header: t('pennantplus.features.feature'), meta: { class: { td: 'font-medium' } } },
  { accessorKey: 'scope', header: t('pennantplus.features.scope') },
  { accessorKey: 'value', header: t('pennantplus.features.value') },
  { accessorKey: 'updated_at', header: t('pennantplus.features.updated'), meta: { class: { td: 'text-muted' } } },
  { id: 'actions', header: () => h('span', { class: 'sr-only' }, t('pennantplus.features.actions')), meta: { class: { td: 'text-right' } } }
])

const dates = computed(() => new Intl.DateTimeFormat(locale.value, { dateStyle: 'medium', timeStyle: 'short' }))

function query(): { feature?: string, scope?: string, scope_id?: string, page?: number } {
  const { feature, scope, scope_id: scopeId } = applied.value

  return {
    ...(feature === '' ? {} : { feature }),
    ...(scope === '' ? {} : { scope }),
    ...(scopeId === '' ? {} : { scope_id: scopeId }),
    ...(page.value > 1 ? { page: page.value } : {})
  }
}

async function loadLists(pennantplus: PennantPlusClient): Promise<void> {
  const [known, types] = await Promise.all([
    pennantplus.GET('/features').then(result => pennantPlusData<PennantPlusList<PennantPlusFeature>>(result)),
    pennantplus.GET('/scopes').then(result => pennantPlusData<{ data: PennantPlusScopeType[] }>(result))
  ])

  features.value = known.data
  scopeTypes.value = types.data
}

async function loadValues(pennantplus: PennantPlusClient): Promise<void> {
  const list = pennantPlusData<PennantPlusList<PennantPlusValue>>(await pennantplus.GET('/values', { params: { query: query() } }))

  values.value = list.data
  page.value = list.meta?.current_page ?? page.value
  lastPage.value = list.meta?.last_page ?? 1
}

/** Loads the values, and the features and scope types too unless `valuesOnly`. */
async function load(valuesOnly = false): Promise<void> {
  if (!client) {
    // The route middleware shows the integration as unavailable instead.
    loaded.value = true
    return
  }

  try {
    await Promise.all([valuesOnly ? null : loadLists(client), loadValues(client)])
    failed.value = false
  } catch (error) {
    refused.value = atriumStatus(error) === 403
    failed.value = !refused.value
  } finally {
    loaded.value = true
  }
}

async function applyFilters(): Promise<void> {
  applied.value = { ...filters }
  page.value = 1
  await load(true)
}

async function resetFilters(): Promise<void> {
  Object.assign(filters, { feature: '', scope: '', scope_id: '' })
  await applyFilters()
}

async function goTo(target: number): Promise<void> {
  page.value = target
  await load(true)
}

/** Runs a change; on success shows `done` and reloads (everything, or just the values). */
async function change(run: (pennantplus: PennantPlusClient) => Promise<unknown>, done: string, valuesOnly = true): Promise<boolean> {
  status.value = null

  if (!client || !await action.run(() => run(client))) {
    return false
  }

  status.value = done
  await load(valuesOnly)

  return true
}

async function toggle(value: PennantPlusValue): Promise<void> {
  await change(async pennantplus => pennantPlusData(await pennantplus.PUT('/values', {
    body: { feature: value.feature, scope: value.scope, value: loosely<string>(!value.active) }
  })), t('pennantplus.features.value_saved'))
}

async function forget(value: PennantPlusValue): Promise<void> {
  if (!window.confirm(t('pennantplus.features.forget_confirm'))) {
    return
  }

  await change(async pennantplus => pennantPlusData(await pennantplus.DELETE('/values', {
    params: { query: { feature: value.feature, scope: value.scope } }
  })), t('pennantplus.features.value_forgotten'))
}

async function purge(): Promise<void> {
  const feature = applied.value.feature

  if (feature === '' || !window.confirm(t('pennantplus.features.purge_confirm'))) {
    return
  }

  const purged = await action.run(async () => pennantPlusData(await client!.DELETE('/features/{feature}', {
    params: { path: { feature }, query: { feature } }
  })))

  if (purged) {
    filters.feature = ''
    applied.value = { ...applied.value, feature: '' }
    page.value = 1
    status.value = t('pennantplus.features.feature_purged')
    await load()
  }
}

async function saved(): Promise<void> {
  status.value = t('pennantplus.features.value_saved')
  await load()
}

function updated(at: string | null): string {
  return at === null ? '' : dates.value.format(new Date(at))
}

void load()
</script>

<template>
  <div class="flex flex-col gap-5" data-testid="pennantplus-features">
    <AtriumNoAccess v-if="forbidden" />

    <USkeleton v-else-if="!loaded" class="h-32" />

    <UAlert
      v-else-if="failed"
      color="error"
      variant="subtle"
      icon="i-lucide-circle-alert"
      :title="t('pennantplus.features.load_failed')"
      :actions="[{ label: t('pennantplus.features.retry'), icon: 'i-lucide-refresh-cw', size: 'sm', variant: 'outline', onClick: () => load() }]"
      data-testid="pennantplus-load-error"
    />

    <template v-else>
      <datalist :id="FEATURE_LIST" :data-testid="FEATURE_LIST">
        <option v-for="feature in features" :key="feature.name" :value="feature.name" />
      </datalist>

      <UAlert v-if="status" color="success" variant="subtle" icon="i-lucide-check" :title="status" data-testid="pennantplus-status" />
      <UAlert v-if="action.message.value" color="error" variant="subtle" icon="i-lucide-circle-alert" :title="action.message.value" />

      <PennantPlusSetValue :scope-types="scopeTypes" :feature-list="FEATURE_LIST" @saved="saved" @forbidden="action.forbidden.value = true" />

      <UForm :state="filters" class="grid items-end gap-3 sm:grid-cols-2 lg:grid-cols-4" data-testid="pennantplus-filters" @submit="applyFilters">
        <UFormField :label="t('pennantplus.features.feature')">
          <UInput
            v-model="filters.feature"
            :list="FEATURE_LIST"
            :placeholder="t('pennantplus.features.all_features')"
            autocomplete="off"
            class="w-full"
            data-testid="pennantplus-filter-feature"
          />
        </UFormField>
        <UFormField :label="t('pennantplus.features.scope')">
          <USelect v-model="scopeFilter" :items="scopeItems" class="w-full" data-testid="pennantplus-filter-scope" />
        </UFormField>
        <UFormField :label="t('pennantplus.features.scope_id')">
          <UInput v-model="filters.scope_id" class="w-full" data-testid="pennantplus-filter-scope-id" />
        </UFormField>
        <div class="flex gap-2">
          <UButton type="submit" variant="outline" icon="i-lucide-funnel" :label="t('pennantplus.features.filter')" data-testid="pennantplus-filter" />
          <UButton color="neutral" variant="ghost" icon="i-lucide-x" :label="t('pennantplus.features.reset')" data-testid="pennantplus-reset" @click="resetFilters" />
        </div>
      </UForm>

      <UEmpty
        v-if="values.length === 0"
        icon="i-lucide-flag"
        variant="naked"
        :description="t('pennantplus.features.no_values')"
        data-testid="pennantplus-no-values"
      />

      <UTable
        v-else
        :data="values"
        :columns="columns"
        :get-row-id="(value: PennantPlusValue) => `${value.feature}|${value.scope}`"
        class="rounded-md border border-default"
        data-testid="pennantplus-values"
      >
        <template #scope-cell="{ row }">
          <span :title="row.original.scope">{{ row.original.scope_label }}</span>
          <span v-if="row.original.title" class="block text-xs text-muted">{{ row.original.title }}</span>
        </template>
        <template #value-cell="{ row }">
          <div class="flex items-center gap-2">
            <UBadge
              :color="row.original.active ? 'success' : 'neutral'"
              variant="subtle"
              :label="row.original.active ? t('pennantplus.features.active') : t('pennantplus.features.inactive')"
              :data-status="row.original.active ? 'active' : 'inactive'"
              data-testid="pennantplus-value-status"
            />
            <code v-if="typeof row.original.value !== 'boolean'" class="text-xs">{{ displayValue(row.original.value) }}</code>
          </div>
        </template>
        <template #updated_at-cell="{ row }">
          {{ updated(row.original.updated_at) }}
        </template>
        <template #actions-cell="{ row }">
          <div class="flex justify-end gap-2">
            <UButton
              size="xs"
              variant="outline"
              :icon="row.original.active ? 'i-lucide-ban' : 'i-lucide-circle-check'"
              :label="row.original.active ? t('pennantplus.features.deactivate') : t('pennantplus.features.activate')"
              :loading="action.busy.value"
              data-testid="pennantplus-toggle"
              @click="toggle(row.original)"
            />
            <UButton
              size="xs"
              color="neutral"
              variant="ghost"
              icon="i-lucide-x"
              :label="t('pennantplus.features.forget')"
              data-testid="pennantplus-forget"
              @click="forget(row.original)"
            />
          </div>
        </template>
      </UTable>

      <div v-if="lastPage > 1" class="flex items-center justify-between gap-3 text-sm">
        <UButton
          size="sm"
          color="neutral"
          variant="outline"
          icon="i-lucide-chevron-left"
          :label="t('pennantplus.features.previous')"
          :disabled="page <= 1"
          data-testid="pennantplus-prev"
          @click="goTo(page - 1)"
        />
        <span class="text-muted" data-testid="pennantplus-page">{{ t('pennantplus.features.page', { page, pages: lastPage }) }}</span>
        <UButton
          size="sm"
          color="neutral"
          variant="outline"
          trailing-icon="i-lucide-chevron-right"
          :label="t('pennantplus.features.next')"
          :disabled="page >= lastPage"
          data-testid="pennantplus-next"
          @click="goTo(page + 1)"
        />
      </div>

      <div v-if="applied.feature !== ''">
        <UButton
          color="error"
          variant="outline"
          size="sm"
          icon="i-lucide-trash"
          :label="t('pennantplus.features.purge', { feature: applied.feature })"
          :loading="action.busy.value"
          data-testid="pennantplus-purge"
          @click="purge"
        />
      </div>
    </template>
  </div>
</template>
