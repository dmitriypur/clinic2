import assert from 'node:assert/strict'
import { createRequire } from 'node:module'
import { readFile } from 'node:fs/promises'
import { fileURLToPath } from 'node:url'
import { gzipSync } from 'node:zlib'
import test from 'node:test'

const require = createRequire(import.meta.url)
const postcss = require('postcss')
const tailwind = require('tailwindcss')
const loadConfig = require('tailwindcss/loadConfig')
const config = loadConfig(fileURLToPath(new URL('../../tailwind.config.js', import.meta.url)))

// No templates: these utilities must also work in HTML entered through the CMS.
const cmsUtilities = postcss([tailwind({
  ...config,
  content: [{ raw: '', extension: 'html' }],
})]).process('@tailwind utilities;', { from: undefined })

function declarations(result, className) {
  const selector = `.${className.replace(/([^a-zA-Z0-9_-])/g, '\\$1')}`
  const values = {}
  result.root.walkRules(rule => {
    // Spacing utilities and rich-text classes apply their declarations to children.
    if (rule.selector !== selector && !rule.selector.startsWith(`${selector} `) && !rule.selector.startsWith(`${selector}:`)) return
    rule.walkDecls(declaration => { values[declaration.prop] = declaration.value })
  })
  return values
}

test('public CSS preserves dynamic CMS grids, spacing, sizes and colours without template matches', async () => {
  const css = await cmsUtilities
  for (let columns = 1; columns <= 12; columns++) {
    for (const variant of ['', 'sm:', 'md:', 'lg:', 'xl:']) {
      assert.equal(
        declarations(css, `${variant}grid-cols-${columns}`)['grid-template-columns'],
        `repeat(${columns}, minmax(0, 1fr))`,
      )
    }
  }
  for (const gap of [0, 2, 4, 6, 8, 10, 12]) {
    assert.ok(declarations(css, `gap-${gap}`).gap, `CMS gap-${gap} must be generated`)
  }
  assert.equal(declarations(css, 'md:p-8').padding, '2rem')
  assert.equal(declarations(css, 'px-6')['padding-left'], '1.5rem')
  assert.equal(declarations(css, 'py-4')['padding-top'], '1rem')
  assert.equal(declarations(css, 'min-h-24')['min-height'], '6rem')
  assert.equal(declarations(css, 'w-full').width, '100%')
  assert.equal(declarations(css, 'md:rounded-20')['border-radius'], '1.25rem')
  for (const colour of ['bg-white', 'bg-surface', 'bg-surface-subdued', 'bg-action-primary-light']) {
    assert.ok(declarations(css, colour)['background-color'], `${colour} must be generated`)
  }
  assert.ok(declarations(css, 'text-heading').color)
  assert.ok(declarations(css, 'hover:bg-action-primary-hovered')['background-color'])
  assert.ok(declarations(css, 'md:bg-surface-subdued')['background-color'])
})

test('CMS safelist does not generate unrelated positioning, colour opacity and directional radius families', async () => {
  const css = await cmsUtilities
  for (const className of ['xl:-top-12', 'md:top-12', 'xl:rounded-tr-3xl', 'sm:bg-white/95']) {
    assert.deepEqual(declarations(css, className), {}, `${className} must require an explicit template or safelist entry`)
  }
  assert.ok(gzipSync(css.css).length < 20_000, 'CMS utility gzip budget is 20 KB')
})

test('classes outside the CMS safelist are still generated when explicitly used in templates', async () => {
  const css = await postcss([tailwind({
    ...config,
    safelist: [],
    content: [{ raw: '<div class="md:top-12 sm:bg-white/95 xl:rounded-tr-3xl"></div>', extension: 'html' }],
  })]).process('@tailwind utilities;', { from: undefined })
  assert.equal(declarations(css, 'md:top-12').top, '3rem')
  assert.ok(declarations(css, 'sm:bg-white/95')['background-color'])
  assert.equal(declarations(css, 'xl:rounded-tr-3xl')['border-top-right-radius'], '1.5rem')
})

test('public stylesheet retains the existing CMS class fixture and dynamic step colours', async () => {
  const source = new URL('../../resources/css/app.css', import.meta.url)
  const css = await postcss([tailwind(config)]).process(await readFile(source, 'utf8'), {
    from: fileURLToPath(source),
  })
  // Classes found in local blocks/pages/categories/reviews, including class arrays.
  // Custom classes already absent from the baseline CSS are deliberately excluded.
  const classes = `-mx-5 bg-action-primary-light bg-surface bg-surface-subdued bg-white
    font-bold font-semibold gap-4 h-full list-disc max-w-3xl mb-8
    md:mx-0 md:p-9 md:px-10 md:px-12 md:py-10 md:py-6 md:rounded-20 md:rounded-xl
    md:text-4xl md:text-xl min-h-24 ml-4 mt-6 mt-8 mx-auto p-4 p-6 px-6 py-4
    rounded-20 rounded-lg space-y-4 text-2xl text-center text-heading text-lg text-xl
    w-auto w-full bg-action-primary-100 bg-action-primary-200 bg-action-primary-300
    bg-action-primary-400`.split(/\s+/)
  for (const className of classes) {
    assert.ok(Object.keys(declarations(css, className)).length, `${className} must remain in public CSS`)
  }
  let contentParagraphRule = false
  css.root.walkRules('.content-block p', () => { contentParagraphRule = true })
  assert.ok(contentParagraphRule, 'rich CMS text must retain its paragraph styling')
})

test('Vue cloak overrides display utilities until a component is mounted', async () => {
  const source = new URL('../../resources/css/app.css', import.meta.url)
  const css = await postcss([tailwind(config)]).process(await readFile(source, 'utf8'), {
    from: fileURLToPath(source),
  })
  let cloakDisplay

  css.root.walkRules('[v-cloak]', rule => {
    rule.walkDecls('display', declaration => {
      cloakDisplay = {
        value: declaration.value,
        important: declaration.important,
      }
    })
  })

  assert.deepEqual(cloakDisplay, { value: 'none', important: true })
})
