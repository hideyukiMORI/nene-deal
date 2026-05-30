import type { LoginRequestDto } from './api-types'
import type { LoginInput } from './model'

export function mapLoginInputToDto(input: LoginInput): LoginRequestDto {
  return { email: input.email, password: input.password }
}
