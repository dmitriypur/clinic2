import assert from 'node:assert/strict'
import test from 'node:test'

import controller from '../../resources/js/components/MobileBottomNavigation/MobileBottomNavigationController.js'
import { eventBus } from '../../resources/js/eventBus.js'

function context() {
  const instance = {}

  for (const [name, method] of Object.entries(controller.methods)) {
    instance[name] = method.bind(instance)
  }

  return instance
}

test('bottom navigation has no separate services overlay state', () => {
  assert.equal(Object.hasOwn(controller.methods, 'toggleServices'), false)
  assert.equal(Object.hasOwn(controller.methods, 'closeServices'), false)
  assert.equal(Object.hasOwn(controller.methods, 'handleKeydown'), false)
})

test('booking button requests BookingWidgetV3 through the shared launcher', () => {
  const instance = context()
  let target = null

  eventBus.$once('openBookingWidgetV3', (value) => { target = value })

  instance.openBookingWidget()

  assert.equal(target, 'otpravka-formy')
})
