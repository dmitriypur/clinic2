import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import test from 'node:test'

async function loadTopBarComponent() {
  const componentFile = new URL(
    '../../resources/js/components/TopBar/TopBar.vue',
    import.meta.url,
  )
  const source = await readFile(componentFile, 'utf8')
  const script = source.match(/<script>([\s\S]*?)<\/script>/)?.[1]

  if (script == null) {
    throw new Error('TopBar component script was not found')
  }

  const executableScript = script
    .replace(/^import[^\n]+\n/gm, '')
    .replace('export default', 'return')

  return new Function(
    'eventBus',
    'classNames',
    'ScrollLockManager',
    'CitySwitcher',
    executableScript,
  )(
    { $on() {}, $off() {} },
    (...classes) => classes.filter(Boolean).join(' '),
    class {
      registerScrollLock() {}
      unregisterScrollLock() {}
    },
    {},
  )
}

function createTopBarInstance(component, document) {
  const scrollLockManager = {
    locked: false,
    registerScrollLock() { this.locked = true },
    unregisterScrollLock() { this.locked = false },
  }
  const menuTrigger = {
    focused: false,
    focus() { this.focused = true },
  }
  let active = false
  const instance = {
    $el: { classList: { add() {}, remove() {} } },
    $refs: { menuTrigger },
    $nextTick(callback) { callback() },
    ...component.data(),
    ...component.methods,
    scrollLockManager,
  }

  instance.handleKeydown = instance.handleKeydown.bind(instance)

  Object.defineProperty(instance, 'active', {
    get() { return active },
    set(value) {
      active = value
      component.watch.active.call(instance, value)
    },
  })

  return { instance, menuTrigger, scrollLockManager }
}

test('TopBar closes an open mobile menu with Escape, unlocks scrolling, and restores focus', async () => {
  const listeners = new Map()
  const previousDocument = globalThis.document
  const previousWindow = globalThis.window
  globalThis.document = {
    body: {},
    addEventListener(event, listener) { listeners.set(event, listener) },
    removeEventListener(event, listener) {
      if (listeners.get(event) === listener) listeners.delete(event)
    },
  }
  globalThis.window = { innerWidth: 390 }

  try {
    const component = await loadTopBarComponent()
    const { instance, menuTrigger, scrollLockManager } = createTopBarInstance(component, globalThis.document)
    instance.active = true

    listeners.get('keydown')({ key: 'Escape' })

    assert.equal(instance.active, false)
    assert.equal(scrollLockManager.locked, false)
    assert.equal(menuTrigger.focused, true)
  } finally {
    globalThis.document = previousDocument
    globalThis.window = previousWindow
  }
})

test('TopBar removes its Escape listener when the mobile menu closes or is destroyed', async () => {
  const listeners = new Map()
  const previousDocument = globalThis.document
  const previousWindow = globalThis.window
  globalThis.document = {
    body: {},
    addEventListener(event, listener) { listeners.set(event, listener) },
    removeEventListener(event, listener) {
      if (listeners.get(event) === listener) listeners.delete(event)
    },
  }
  globalThis.window = {
    innerWidth: 390,
    matchMedia() { return { addEventListener() {}, removeEventListener() {} } },
  }

  try {
    const component = await loadTopBarComponent()
    const { instance } = createTopBarInstance(component, globalThis.document)
    instance.active = true
    assert.ok(listeners.has('keydown'))

    instance.active = false
    assert.equal(listeners.has('keydown'), false)

    component.mounted.call(instance)
    instance.active = true
    component.beforeDestroy.call(instance)
    assert.equal(listeners.has('keydown'), false)
  } finally {
    globalThis.document = previousDocument
    globalThis.window = previousWindow
  }
})
