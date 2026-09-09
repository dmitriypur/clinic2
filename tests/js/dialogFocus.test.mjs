import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import test from 'node:test'

async function loadDialogComponent() {
  const componentFile = new URL(
    '../../resources/js/components/Modal/components/Dialog/Dialog.vue',
    import.meta.url,
  )
  const source = await readFile(componentFile, 'utf8')
  const script = source.match(/<script>([\s\S]*?)<\/script>/)?.[1]

  if (script == null) {
    throw new Error('Dialog component script was not found')
  }

  const executableScript = script
    .replace(/^import[^\n]+\n/gm, '')
    .replace('export default', 'return')

  return new Function('KeypressListener', 'classNames', executableScript)(
    {},
    (...classes) => classes.filter(Boolean).join(' '),
  )
}

async function loadBookingWidgetModalComponent() {
  const componentFile = new URL(
    '../../resources/js/components/BookingWidgetV3/components/BookingWidgetModal.vue',
    import.meta.url,
  )
  const source = await readFile(componentFile, 'utf8')
  const script = source.match(/<script>([\s\S]*?)<\/script>/)?.[1]

  if (script == null) {
    throw new Error('BookingWidgetModal component script was not found')
  }

  const executableScript = script
    .replace(/^import[^\n]+\n/gm, '')
    .replace('export default', 'return')

  return new Function('Backdrop', 'Dialog', 'Scrollable', 'classNames', executableScript)(
    {},
    {},
    {},
    (...classes) => classes.filter(Boolean).join(' '),
  )
}

test('Dialog traps Tab focus and restores the element that opened it', async () => {
  const component = await loadDialogComponent()
  const trigger = { focus() { activeElement = this } }
  const firstControl = {
    focus() { activeElement = this },
    getAttribute() { return null },
  }
  const lastControl = {
    focus() { activeElement = this },
    getAttribute() { return null },
  }
  const dialog = {
    focus() { activeElement = this },
    contains(element) {
      return [firstControl, lastControl].includes(element)
    },
    querySelectorAll() {
      return [firstControl, lastControl]
    },
  }
  let activeElement = trigger
  const previousDocument = globalThis.document
  globalThis.document = { activeElement }

  try {
    const instance = {
      $refs: { dialog },
      $nextTick(callback) { callback() },
      ...component.data(),
      ...component.methods,
    }

    component.mounted.call(instance)
    assert.equal(activeElement, firstControl)

    let prevented = false
    instance.onKeydown({
      key: 'Tab',
      shiftKey: false,
      target: lastControl,
      preventDefault() { prevented = true },
    })
    assert.equal(activeElement, firstControl)
    assert.equal(prevented, true)

    prevented = false
    instance.onKeydown({
      key: 'Tab',
      shiftKey: true,
      target: firstControl,
      preventDefault() { prevented = true },
    })
    assert.equal(activeElement, lastControl)
    assert.equal(prevented, true)

    component.beforeDestroy.call(instance)
    assert.equal(activeElement, trigger)
  } finally {
    globalThis.document = previousDocument
  }
})

test('BookingWidgetModal creates an ID for its dialog label', async () => {
  const component = await loadBookingWidgetModalComponent()

  assert.equal(
    component.computed.dialogTitleId.call({ _uid: 42 }),
    'BookingWidgetModal-title-42',
  )
})
