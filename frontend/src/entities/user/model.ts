export type OperatorRole = 'admin' | 'operator'

export interface OperatorUser {
  id: string
  organizationId: string
  email: string
  role: OperatorRole
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
}
