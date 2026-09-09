import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import test from 'node:test'

async function loadAppFilterComponent(axios) {
  const componentFile = new URL(
    '../../resources/js/components/AppFilter/AppFilter.vue',
    import.meta.url,
  )
  const source = await readFile(componentFile, 'utf8')
  const script = source.match(/<script>([\s\S]*?)<\/script>/)?.[1]

  if (script == null) {
    throw new Error('AppFilter component script was not found')
  }

  const executableScript = script
    .replace(/^import[^\n]+\n/gm, '')
    .replace('export default', 'return')

  return new Function('axios', executableScript)(axios)
}

function deferred() {
  let resolve
  let reject
  const promise = new Promise((resolvePromise, rejectPromise) => {
    resolve = resolvePromise
    reject = rejectPromise
  })

  return { promise, resolve, reject }
}

async function flushPromises() {
  await new Promise(resolve => setImmediate(resolve))
}

function controlledRequest() {
  const handlers = {}
  const chain = {
    then(handler) {
      handlers.resolve = handler
      return chain
    },
    catch(handler) {
      handlers.reject = handler
      return chain
    },
    finally(handler) {
      handlers.finally = handler
      return chain
    },
  }

  return {
    chain,
    reject(error) {
      handlers.reject?.(error)
      handlers.finally?.()
    },
  }
}

test('AppFilter ignores a response from an older request', async () => {
  const requests = []
  const axios = {
    get() {
      const request = deferred()
      requests.push(request)
      return request.promise
    },
  }
  const component = await loadAppFilterComponent(axios)
  const instance = {
    $refs: { handle: { role: '/reviews' } },
    ...component.data(),
    ...component.methods,
  }

  instance.sendFilter('/reviews')
  instance.sendFilter('/reviews')

  requests[1].resolve({
    data: { data: [{ id: 'new' }], meta: { total: 1, per_page: 1 } },
  })
  await flushPromises()

  requests[0].resolve({
    data: { data: [{ id: 'old' }], meta: { total: 1, per_page: 1 } },
  })
  await flushPromises()

  assert.deepEqual(instance.reviewArr, [{ id: 'new' }])
})

test('AppFilter exposes the active request error and finishes loading', async () => {
  const request = controlledRequest()
  const component = await loadAppFilterComponent({
    get() {
      return request.chain
    },
  })
  const instance = {
    $refs: { handle: { role: '/reviews' } },
    ...component.data(),
    ...component.methods,
  }
  const previousSetTimeout = globalThis.setTimeout
  globalThis.setTimeout = callback => callback()

  try {
    instance.sendFilter('/reviews')
    request.reject(new Error('Network error'))

    assert.equal(
      instance.errorMessage,
      'Не удалось загрузить результаты. Попробуйте ещё раз.',
    )
    assert.equal(instance.loading, false)
  } finally {
    globalThis.setTimeout = previousSetTimeout
  }
})

for (const [method, filterName, initialValue, argument] of [
  ['addResource', 'resources', [], 10],
  ['addDoctor', 'doctors', [], 20],
  ['addService', 'services', [], 30],
  ['addTag', 'tags', [], 40],
  ['clearFilterTag', 'tags', [50], undefined],
]) {
  test(`AppFilter resets pagination when ${method} changes a filter`, async () => {
    const component = await loadAppFilterComponent({ get() {} })
    const instance = {
      $refs: { handle: { role: '/reviews' } },
      ...component.data(),
      ...component.methods,
      sendFilter() {},
    }
    instance.filters[filterName] = [...initialValue]
    instance.perpage = 4

    instance[method](argument)

    assert.equal(instance.perpage, 1)
  })
}
