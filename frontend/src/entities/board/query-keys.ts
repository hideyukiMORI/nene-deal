export const boardKeys = {
  all: ['board'] as const,
  view: (params: { includeTerminal: boolean; includeDeleted: boolean }) =>
    [...boardKeys.all, params] as const,
}
