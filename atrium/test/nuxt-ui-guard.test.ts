import { rawHtmlIn } from '@refactor-circus/atrium/testing'
import { describe, expect, it } from 'vitest'

/*
 * PennantPlus's module composes Nuxt UI, as Atrium's own components do
 * (Atrium D-091, D-093): no page or component under `src/` renders a raw
 * `<button>`, `<input>`, `<select>`, `<textarea>`, `<table>`, `<details>` or
 * `<a href>`. The feature names' `<datalist>` is not one of them.
 */
describe('the PennantPlus module composes Nuxt UI', () => {
  it('renders no raw HTML controls', () => {
    expect(rawHtmlIn('src')).toEqual([])
  })
})
