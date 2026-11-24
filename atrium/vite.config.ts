import ui from '@nuxt/ui/vite'
import type { PluginOption } from 'vite'
import { atriumAutoImports, atriumComponentResolver, atriumModule, atriumUiConfig } from '@refactor-circus/atrium/vite'

// PennantPlus's Atrium module: `pnpm build` writes ../dist/atrium (committed), `pnpm test` runs Vitest.
const config = atriumModule({
  key: 'pennantplus',
  outDir: '../dist/atrium',
  inputs: ['src', 'package.json', 'vite.config.ts', 'pnpm-lock.yaml'],
  test: { setupFiles: ['test/setup.ts'] }
})

// Workaround, tests only: installed from GitHub, the SDK's own components sit
// under node_modules, which Nuxt UI's component resolver skips, so their
// `<Atrium*>` and `<U*>` tags would not resolve. Same Nuxt UI plugin as the
// preset's, with an exclude that lets the SDK through. Drop it once Atrium's
// preset does this itself.
if (process.env.VITEST) {
  const [vue] = config.plugins as PluginOption[]

  config.plugins = [vue, ui({
    ui: atriumUiConfig,
    colorMode: true,
    autoImport: { imports: atriumAutoImports({ ui: false }) as never, dts: false, vueTemplate: true, exclude: [/[\\/]node_modules[\\/]/, /[\\/]\.git[\\/]/] },
    components: { dirs: [], dts: false, resolvers: [atriumComponentResolver({ ui: false, direct: true })], exclude: /[\\/]node_modules[\\/](?!\.pnpm[\\/]|@nuxt[\\/]ui[\\/]|@refactor-circus[\\/]atrium[\\/])|[\\/]\.git[\\/]/ }
  })]
}

export default config
