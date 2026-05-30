import { useLogin, type LoginInput } from '@/entities/auth'
import { authStore } from '@/shared/auth'
import { mapProblemDetailsToMessageKey, type MessageKey } from '@/shared/i18n'

export interface LoginPage {
  submit: (input: LoginInput) => Promise<boolean>
  pending: boolean
  errorKey: MessageKey | null
}

export function useLoginPage(): LoginPage {
  const login = useLogin()

  return {
    submit: async (input: LoginInput): Promise<boolean> => {
      try {
        const token = await login.mutateAsync(input)
        authStore.setToken(token)
        return true
      } catch {
        return false
      }
    },
    pending: login.isPending,
    errorKey: login.error !== null ? mapProblemDetailsToMessageKey(login.error) : null,
  }
}
