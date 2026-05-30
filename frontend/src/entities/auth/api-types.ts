export interface LoginRequestDto {
  email: string
  password: string
}

export interface LoginResultDto {
  token: string
}
