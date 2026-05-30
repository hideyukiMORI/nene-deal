export interface ValidationError {
  field: string
  message: string
  code: string
}

export interface ProblemDetails {
  type: string
  title: string
  status: number
  detail?: string
  instance: string
  errors?: readonly ValidationError[]
}

/**
 * Typed error thrown by the API client from an `application/problem+json` body
 * (RFC 7807). The single error shape the UI maps to user feedback.
 */
export class AppError extends Error {
  readonly status: number
  readonly type: string
  readonly title: string
  readonly detail?: string
  readonly instance: string
  readonly errors?: readonly ValidationError[]

  constructor(problem: ProblemDetails) {
    super(problem.title)
    this.name = 'AppError'
    this.status = problem.status
    this.type = problem.type
    this.title = problem.title
    this.instance = problem.instance
    if (problem.detail !== undefined) {
      this.detail = problem.detail
    }
    if (problem.errors !== undefined) {
      this.errors = problem.errors
    }
  }

  get isRetryable(): boolean {
    return this.status >= 500 || this.status === 429
  }
}

export async function parseProblemDetails(response: Response): Promise<AppError> {
  try {
    const body = (await response.json()) as ProblemDetails
    return new AppError({
      ...body,
      status: body.status,
      instance: body.instance,
    })
  } catch {
    return new AppError({
      type: 'about:blank',
      title: response.statusText || 'Request failed',
      status: response.status,
      instance: response.url,
    })
  }
}
