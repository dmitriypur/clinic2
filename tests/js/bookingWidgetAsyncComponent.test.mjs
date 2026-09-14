import assert from 'node:assert/strict'
import test from 'node:test'

async function loadAsyncComponentHelpers() {
  try {
    return await import(
      '../../resources/js/components/BookingWidgetV3/bookingWidgetAsyncComponent.js'
    )
  } catch (error) {
    assert.fail(`booking widget async fallback is unavailable: ${error.code || error.message}`)
  }
}

test('booking widget async factory shows loading immediately and exposes an error fallback', async () => {
  const {
    BookingWidgetLoadError,
    BookingWidgetLoading,
    createBookingWidgetV3AsyncComponent,
  } = await loadAsyncComponentHelpers()
  const componentPromise = Promise.resolve({ default: { name: 'BookingWidgetV3' } })
  const factory = createBookingWidgetV3AsyncComponent(() => componentPromise)

  const definition = factory()

  assert.equal(definition.component, componentPromise)
  assert.equal(definition.loading, BookingWidgetLoading)
  assert.equal(definition.error, BookingWidgetLoadError)
  assert.equal(definition.delay, 0)
  assert.equal(definition.timeout, 15_000)
})

test('booking widget error fallback reloads the page only after an explicit retry', async () => {
  const { BookingWidgetLoadError } = await loadAsyncComponentHelpers()
  const previousWindow = globalThis.window
  let reloads = 0
  globalThis.window = {
    location: {
      reload() {
        reloads += 1
      },
    },
  }

  try {
    assert.equal(reloads, 0)
    BookingWidgetLoadError.methods.reloadPage()
    assert.equal(reloads, 1)
  } finally {
    globalThis.window = previousWindow
  }
})
