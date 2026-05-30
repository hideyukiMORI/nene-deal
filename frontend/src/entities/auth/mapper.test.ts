import { describe, expect, it } from 'vitest'
import { mapLoginInputToDto } from './mapper'

describe('auth mapper', () => {
  it('maps login input to the request dto', () => {
    expect(mapLoginInputToDto({ email: 'operator@nene-deal.test', password: 'password' })).toEqual({
      email: 'operator@nene-deal.test',
      password: 'password',
    })
  })
})
