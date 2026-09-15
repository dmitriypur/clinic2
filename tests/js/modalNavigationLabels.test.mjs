import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import { createRequire } from 'node:module'
import test from 'node:test'

const require = createRequire(import.meta.url)
const Vue = require('vue/dist/vue.common.js')

async function renderButton(componentName) {
  const componentFile = new URL(
    `../../resources/js/components/Modal/components/${componentName}/${componentName}.vue`,
    import.meta.url,
  )
  const source = await readFile(componentFile, 'utf8')
  const template = source.match(/<template>([\s\S]*?)<\/template>/)?.[1]

  if (template == null) {
    throw new Error(`${componentName} template was not found`)
  }

  const { errors, render } = Vue.compile(template)

  assert.deepEqual(errors ?? [], [])

  return render.call({
    className: 'modal-navigation-button',
    onClick() {},
    _c(tag, data, children) {
      return { tag, data, children }
    },
  })
}

test('PrevButton exposes the previous element as its accessible name', async () => {
  const button = await renderButton('PrevButton')

  assert.equal(button.tag, 'button')
  assert.equal(button.data.attrs['aria-label'], 'Предыдущий элемент')
})

test('NextButton exposes the next element as its accessible name', async () => {
  const button = await renderButton('NextButton')

  assert.equal(button.tag, 'button')
  assert.equal(button.data.attrs['aria-label'], 'Следующий элемент')
})
