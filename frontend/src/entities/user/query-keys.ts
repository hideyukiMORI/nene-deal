export const userKeys = {
  all: ['users'] as const,
  detail: (id: string) => ['users', id] as const,
}
