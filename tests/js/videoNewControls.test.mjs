import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import test from 'node:test'

test('video overlay is removed while native controls are active', async () => {
  const template = await readFile(new URL('../../resources/views/components/block/video-new.blade.php', import.meta.url), 'utf8')
  assert.match(template, /<div\s+v-if="!hideBackdrop"\s+:class="backdropClassName">/)
  assert.match(template, /:controls="hideBackdrop"/)
})
