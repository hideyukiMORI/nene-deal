export const stageKeys = {
  all: ['pipeline-stages'] as const,
  list: () => [...stageKeys.all, 'list'] as const,
}
