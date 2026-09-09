import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import test from 'node:test'

async function loadScrollableComponent() {
  const componentFile = new URL(
    '../../resources/js/components/Scrollable/Scrollable.vue',
    import.meta.url,
  )
  const source = await readFile(componentFile, 'utf8')
  const script = source.match(/<script>([\s\S]*?)<\/script>/)?.[1]

  if (script == null) {
    throw new Error('Scrollable component script was not found')
  }

  const executableScript = script
    .replace(/import[^\n]+\n/g, '')
    .replace('export default', 'return')

  return new Function('debounce', 'classNames', executableScript)(
    (callback) => callback,
    (...classes) => classes.filter(Boolean).join(' '),
  )
}

test('Scrollable removes its browser listeners when it is destroyed', async () => {
  const component = await loadScrollableComponent()
  const listeners = new Map()
  const scrollArea = {
    addEventListener(event, listener) {
      listeners.set(`scroll-area:${event}`, listener)
    },
    removeEventListener(event, listener) {
      if (listeners.get(`scroll-area:${event}`) === listener) {
        listeners.delete(`scroll-area:${event}`)
      }
    },
  }
  const previousWindow = globalThis.window

  globalThis.window = {
    addEventListener(event, listener) {
      listeners.set(`window:${event}`, listener)
    },
    removeEventListener(event, listener) {
      if (listeners.get(`window:${event}`) === listener) {
        listeners.delete(`window:${event}`)
      }
    },
    requestAnimationFrame() {},
  }

  try {
    const instance = {
      $refs: { scrollArea },
      scrollPosition: 0,
      handleScroll() {},
      handleResize() {},
    }

    component.mounted.call(instance)
    component.beforeDestroy.call(instance)

    assert.deepEqual([...listeners.keys()], [])
  } finally {
    globalThis.window = previousWindow
  }
})
