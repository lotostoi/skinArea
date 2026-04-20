export function extractApiErrorMessage(error: unknown, fallback: string): string {
  if (error && typeof error === 'object' && 'response' in error) {
    const response = (error as { response?: unknown }).response
    if (response && typeof response === 'object' && 'data' in response) {
      const data = (response as { data?: unknown }).data
      if (data && typeof data === 'object' && 'message' in data) {
        const message = (data as { message?: unknown }).message
        if (typeof message === 'string' && message.length > 0) {
          return message
        }
      }
    }
  }
  if (error instanceof Error && error.message) {
    return error.message
  }
  return fallback
}
