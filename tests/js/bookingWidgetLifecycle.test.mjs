import assert from 'node:assert/strict'
import test from 'node:test'

import { buildBookingLaunchContextFromSearchParams } from '../../resources/js/utilities/bookingLaunchContext.js'

async function loadLifecycle() {
  try {
    return await import('../../resources/js/utilities/bookingWidgetLifecycle.js')
  } catch (error) {
    assert.fail(`booking widget lifecycle is unavailable: ${error.code || error.message}`)
  }
}

test('booking widget stays unmounted before the first launch', async () => {
  const { createBookingWidgetLifecycleState } = await loadLifecycle()

  assert.deepEqual(createBookingWidgetLifecycleState(), {
    bookingWidgetV3Mounted: false,
    bookingWidgetV3Active: false,
    bookingWidgetV3Target: null,
    bookingWidgetV3Mode: null,
    bookingWidgetV3LaunchContext: null,
  })
})

test('first launch mounts and opens the widget with its complete context', async () => {
  const {
    activateBookingWidgetV3,
    createBookingWidgetLifecycleState,
  } = await loadLifecycle()
  const state = createBookingWidgetLifecycleState()
  const launchContext = {
    entry: 'doctor',
    doctorId: 'doctor-42',
    branchId: null,
  }

  activateBookingWidgetV3(state, {
    target: 'doctor-card',
    mode: 'doctor',
    launchContext,
  })

  assert.deepEqual(state, {
    bookingWidgetV3Mounted: true,
    bookingWidgetV3Active: true,
    bookingWidgetV3Target: 'doctor-card',
    bookingWidgetV3Mode: 'doctor',
    bookingWidgetV3LaunchContext: launchContext,
  })
})

test('URL launch context opens the lazily mounted widget', async () => {
  const {
    activateBookingWidgetV3,
    createBookingWidgetLifecycleState,
  } = await loadLifecycle()
  const state = createBookingWidgetLifecycleState()
  const launchContext = buildBookingLaunchContextFromSearchParams(
    '?booking_entry=clinic&booking_branch_id=branch-7',
  )

  activateBookingWidgetV3(state, {
    mode: launchContext.entry,
    launchContext,
  })

  assert.equal(state.bookingWidgetV3Mounted, true)
  assert.equal(state.bookingWidgetV3Active, true)
  assert.deepEqual(state.bookingWidgetV3LaunchContext, {
    entry: 'clinic',
    doctorId: null,
    branchId: 'branch-7',
  })
})

test('closing clears launch data but keeps the widget mounted for reopening', async () => {
  const {
    activateBookingWidgetV3,
    closeBookingWidgetV3,
    createBookingWidgetLifecycleState,
  } = await loadLifecycle()
  const state = createBookingWidgetLifecycleState()

  activateBookingWidgetV3(state, {
    target: 'first-target',
    mode: 'doctor',
    launchContext: { entry: 'doctor', doctorId: 'doctor-42', branchId: null },
  })
  closeBookingWidgetV3(state)

  assert.deepEqual(state, {
    bookingWidgetV3Mounted: true,
    bookingWidgetV3Active: false,
    bookingWidgetV3Target: null,
    bookingWidgetV3Mode: null,
    bookingWidgetV3LaunchContext: null,
  })
})

test('reopening replaces the previous launch data', async () => {
  const {
    activateBookingWidgetV3,
    closeBookingWidgetV3,
    createBookingWidgetLifecycleState,
  } = await loadLifecycle()
  const state = createBookingWidgetLifecycleState()

  activateBookingWidgetV3(state, {
    target: 'first-target',
    mode: 'doctor',
    launchContext: { entry: 'doctor', doctorId: 'doctor-42', branchId: null },
  })
  closeBookingWidgetV3(state)
  activateBookingWidgetV3(state, {
    target: 'second-target',
    mode: 'clinic',
    launchContext: { entry: 'clinic', doctorId: null, branchId: 'branch-7' },
  })

  assert.deepEqual(state, {
    bookingWidgetV3Mounted: true,
    bookingWidgetV3Active: true,
    bookingWidgetV3Target: 'second-target',
    bookingWidgetV3Mode: 'clinic',
    bookingWidgetV3LaunchContext: {
      entry: 'clinic',
      doctorId: null,
      branchId: 'branch-7',
    },
  })
})
