import type { AxiosError } from 'axios'
import type { ApiErrorResponse } from '@/types/api'

export interface AppError {
  message: string
  errors: Record<string, string[]>
  status: number
}

export function parseApiError(error: unknown): AppError {
  if (isAxiosError(error) && error.response) {
    const data = error.response.data as ApiErrorResponse
    return {
      message: data.message || 'An unexpected error occurred',
      errors: data.errors || {},
      status: error.response.status,
    }
  }

  return {
    message: error instanceof Error ? error.message : 'An unexpected error occurred',
    errors: {},
    status: 0,
  }
}

export function getFieldErrors(error: AppError, field: string): string[] {
  return error.errors[field] || []
}

export function getFirstError(error: AppError): string {
  const firstField = Object.keys(error.errors)[0]
  if (firstField) {
    return error.errors[firstField][0]
  }
  return error.message
}

function isAxiosError(error: unknown): error is AxiosError {
  return (error as AxiosError)?.isAxiosError === true
}
