import assert from 'node:assert/strict'
import test from 'node:test'

import {
  buildFrameCatalogQuery,
  frameCatalogPageSize,
  isLatestFrameCatalogRequest,
  normalizeGenderFilters,
  toggleFrameCatalogFilter,
} from '../../resources/js/components/FrameCatalog/index.js'

test('toggleFrameCatalogFilter adds a missing value and removes a selected value', () => {
  assert.deepEqual(toggleFrameCatalogFilter([1], 2), [1, 2])
  assert.deepEqual(toggleFrameCatalogFilter([1, 2], 1), [2])
})

test('buildFrameCatalogQuery preserves multiple ages and pagination', () => {
  const params = new URLSearchParams(buildFrameCatalogQuery({
    ages: [10, 20],
    genders: ['girl'],
    offset: 4,
    limit: 4,
  }))

  assert.deepEqual(params.getAll('ages[]'), ['10', '20'])
  assert.deepEqual(params.getAll('genders[]'), ['girl'])
  assert.equal(params.get('offset'), '4')
  assert.equal(params.get('limit'), '4')
})

test('both selected genders mean no gender restriction in the request', () => {
  assert.deepEqual(normalizeGenderFilters(['girl', 'boy']), [])

  const params = new URLSearchParams(buildFrameCatalogQuery({
    ages: [],
    genders: ['boy', 'girl'],
    offset: 0,
    limit: 6,
  }))

  assert.deepEqual(params.getAll('genders[]'), [])
})

test('frameCatalogPageSize uses four cards on mobile and six from tablet', () => {
  assert.equal(frameCatalogPageSize(false), 4)
  assert.equal(frameCatalogPageSize(true), 6)
})

test('only the latest request is allowed to update the catalog', () => {
  assert.equal(isLatestFrameCatalogRequest(3, 3), true)
  assert.equal(isLatestFrameCatalogRequest(2, 3), false)
})
