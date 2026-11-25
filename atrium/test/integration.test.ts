import { describe, expect, it } from 'vitest'
import pennantplus, { screens } from '../src/atrium'
import { expectIntegrationManifest, hasI18nKey, missingI18nKeys, useRouter, withSetup } from '@refactor-circus/atrium/testing'
import locale from '../src/locales/pennantplus.en.json'

/*
 * The PennantPlus integration (AC 49, 53, 58; D-090): its manifest
 * (`src/integration.json`, built to `dist/atrium/integration.json`) is valid
 * as Atrium checks it; every screen and nav path it declares has its page;
 * `src/atrium.ts` maps each screen key to that page's route; and every `t()`
 * key it uses exists.
 */

const manifest = expectIntegrationManifest('src/integration.json', 'pennantplus')

const manifestScreens = manifest.screens ?? []

const manifestPaths = (manifest.navigation ?? []).map(item => item.path as string)

/** A Nuxt route path as Atrium registers it: `:param()` read as `:param`. */
function declaredPath(path: string): string {
  return path.replace(/\(\)/g, '')
}

describe('the pennantplus manifest', () => {
  it('declares the one screen and nav item of AC 58, and nothing else', () => {
    expect(manifest.features).toEqual(['pennantplus.atrium'])
    expect(manifestScreens.map(screen => [screen.key, screen.kind, screen.path])).toEqual([
      ['pennantplus.features', 'list', '/pennantplus/features']
    ])
    expect(manifestPaths).toEqual(['/pennantplus/features'])
    expect(manifest.widgets ?? []).toEqual([])
    expect(manifest.settings ?? null).toBeNull()
    expect(manifest.search_sources ?? []).toEqual([])
    expect(manifest.hooks ?? []).toEqual([])
  })

  it('has a screens entry in src/atrium.ts for every screen key, and nothing else', () => {
    expect(Object.keys(screens).sort()).toEqual(manifestScreens.map(screen => screen.key).sort())
  })

  it('has a page for every screen, at the path the manifest declares', async () => {
    const routes = (await withSetup(() => useRouter())).getRoutes()

    for (const screen of manifestScreens) {
      const route = routes.find(candidate => candidate.name === screens[screen.key])

      expect(route, screen.key).toBeDefined()
      expect(declaredPath(route!.path), screen.key).toBe(screen.path)
    }
  })

  it('has a page for every nav link', async () => {
    const router = await withSetup(() => useRouter())

    for (const path of manifestPaths) {
      expect(router.resolve(path).name, path).toBe(screens['pennantplus.features'])
    }
  })
})

describe('the pennantplus registrations', () => {
  it('registers nothing on the registry: PennantPlus has no widget, panel, search source or live resolver', () => {
    expect(pennantplus.widgets).toBeUndefined()
    expect(pennantplus.extensions).toBeUndefined()
    expect(pennantplus.settingsPanels).toBeUndefined()
    expect(pennantplus.searchSources).toBeUndefined()
    expect(pennantplus.liveResolvers).toBeUndefined()
  })
})

describe('the pennantplus strings', () => {
  it('uses only t() keys its locale files hold', () => {
    expect(missingI18nKeys('pennantplus')).toEqual([])
  })

  it('carries v1\'s confirmation and notices', () => {
    for (const key of ['pennantplus.features.purge_confirm', 'pennantplus.features.forget_confirm', 'pennantplus.features.value_saved', 'pennantplus.features.value_forgotten', 'pennantplus.features.feature_purged']) {
      expect(hasI18nKey(locale, key), key).toBe(true)
    }
  })
})
