import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import test from 'node:test'

async function loadSmoothScrollHandler() {
  const appFile = new URL('../../resources/js/app.js', import.meta.url)
  const source = await readFile(appFile, 'utf8')
  const method = source.match(/handleSmoothScroll\(e\) \{([\s\S]*?)\n    \},\n\n    normalizeBookingWidgetStartMode/)

  if (method == null) {
    throw new Error('Smooth scroll handler was not found')
  }

  return new Function('eventBus', `return function handleSmoothScroll(e) {${method[1]}\n}`)({
    $emit() {},
  })
}

test('skip link moves focus to the main landmark after scrolling to it', async () => {
  const handler = await loadSmoothScrollHandler()
  const previousDocument = globalThis.document
  const previousWindow = globalThis.window
  const main = {
    id: 'main-content',
    focusOptions: null,
    scrollOptions: null,
    focus(options) { this.focusOptions = options },
    scrollIntoView(options) { this.scrollOptions = options },
  }
  let prevented = false

  globalThis.document = {
    querySelector(selector) {
      return selector === '#main-content' ? main : null
    },
  }
  globalThis.window = { innerWidth: 1280 }

  try {
    handler({
      target: {
        getAttribute() { return '#main-content' },
        parentElement: null,
      },
      preventDefault() { prevented = true },
    })

    assert.equal(prevented, true)
    assert.deepEqual(main.scrollOptions, { behavior: 'smooth' })
    assert.deepEqual(main.focusOptions, { preventScroll: true })
  } finally {
    globalThis.document = previousDocument
    globalThis.window = previousWindow
  }
})
