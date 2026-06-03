export const forecastKeys = {
  all: ['forecast'] as const,
  month: (month: string) => [...forecastKeys.all, month] as const,
  current: () => [...forecastKeys.all, 'current'] as const,
}
