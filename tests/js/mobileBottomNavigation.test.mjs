import assert from 'node:assert/strict'
import test from 'node:test'

import controller from '../../resources/js/components/MobileBottomNavigation/MobileBottomNavigationController.js'
import { eventBus } from '../../resources/js/eventBus.js'

function context(open = false) {
  const calls = { lock: 0, unlock: 0, panelFocus: 0, toggleFocus: 0 }
  const instance = {
    servicesOpen: open,
    inertedElements: [],
    scrollLockManager: {
      registerScrollLock: () => calls.lock++,
      unregisterScrollLock: () => calls.unlock++,
    },
    $refs: {
      servicesPanel: {
        focus: () => calls.panelFocus++,
        querySelectorAll: () => [],
      },
      servicesToggle: { focus: () => calls.toggleFocus++ },
    },
    $el: {
      parentElement: { children: [] },
    },
    $nextTick: (callback) => callback(),
  }

  for (const [name, method] of Object.entries(controller.methods)) {
    instance[name] = method.bind(instance)
  }

  return { instance, calls }
}

test('services panel balances scroll locking and restores focus', () => {
  const { instance, calls } = context()

  instance.toggleServices()
  assert.equal(instance.servicesOpen, true)
  assert.deepEqual(calls, { lock: 1, unlock: 0, panelFocus: 1, toggleFocus: 0 })

  instance.closeServices()
  assert.equal(instance.servicesOpen, false)
  assert.deepEqual(calls, { lock: 1, unlock: 1, panelFocus: 1, toggleFocus: 1 })

  instance.closeServices()
  assert.equal(calls.unlock, 1)
})

test('desktop breakpoint closes services without moving focus', () => {
  const { instance, calls } = context(true)

  instance.handleBreakpointChange({ matches: true })

  assert.equal(instance.servicesOpen, false)
  assert.equal(calls.unlock, 1)
  assert.equal(calls.toggleFocus, 0)
})

test('Escape closes services and returns focus to its trigger', () => {
  const { instance, calls } = context(true)

  instance.handleKeydown({ key: 'Escape' })

  assert.equal(instance.servicesOpen, false)
  assert.equal(calls.unlock, 1)
  assert.equal(calls.toggleFocus, 1)
})

test('Tab focus trap uses only links inside the services dialog', () => {
  const { instance } = context(true)
  let firstFocused = 0
  const first = { offsetParent: {}, focus: () => firstFocused++ }
  const last = { offsetParent: {}, focus: () => {} }
  instance.$refs.servicesPanel.querySelectorAll = () => [first, last]
  global.document = { activeElement: last }
  let prevented = 0

  instance.handleKeydown({ key: 'Tab', shiftKey: false, preventDefault: () => prevented++ })

  assert.equal(prevented, 1)
  assert.equal(firstFocused, 1)
  delete global.document
})

test('opening services makes every background sibling inert', () => {
  const { instance } = context()
  const background = { inert: false }
  instance.$el.parentElement.children = [background, instance.$el]

  instance.toggleServices()
  assert.equal(background.inert, true)

  instance.closeServices(false)
  assert.equal(background.inert, false)
})

test('booking button closes services and requests BookingWidgetV3', () => {
  const { instance, calls } = context(true)
  let target = null
  eventBus.$once('openBookingWidgetV3', (value) => { target = value })

  instance.openBookingWidget()

  assert.equal(instance.servicesOpen, false)
  assert.equal(calls.unlock, 1)
  assert.equal(target, 'otpravka-formy')
})
