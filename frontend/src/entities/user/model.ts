export type OperatorRole = 'admin' | 'operator'

export type UserStatus = 'active' | 'disabled'

export interface OperatorUser {
  id: string
  organizationId: string
  email: string
  role: OperatorRole
  status: UserStatus
  createdAt: string
  updatedAt: string
}

export interface CreateUserInput {
  email: string
  password: string
  role: OperatorRole
}

export interface UpdateUserInput {
  email?: string
  role?: OperatorRole
  status?: UserStatus
}
