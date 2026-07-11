import type { OperatorUserDto } from './api-types'
import type { CreateUserInput, OperatorUser, UpdateUserInput } from './model'

export function mapUserDto(dto: OperatorUserDto): OperatorUser {
  return {
    id: dto.id,
    organizationId: dto.organization_id,
    email: dto.email,
    role: dto.role,
    status: dto.status,
    createdAt: dto.created_at,
    updatedAt: dto.updated_at,
  }
}

export function mapCreateUserInput(input: CreateUserInput): Record<string, unknown> {
  return { email: input.email, password: input.password, role: input.role }
}

export function mapUpdateUserInput(input: UpdateUserInput): Record<string, unknown> {
  const body: Record<string, unknown> = {}
  if (input.email !== undefined) body.email = input.email
  if (input.role !== undefined) body.role = input.role
  if (input.status !== undefined) body.status = input.status
  return body
}
