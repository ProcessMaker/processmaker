import { {{ $className }} } from '../api/{{ $tagLower }}.api';

/**
 * Hook to access ProcessMaker {{ ucfirst($tagLower) }} API
 * @param apiClient - API client with authentication handling
 * @returns {{ $className }} instance
 */
export const {{ $hookName }} = (apiClient: {
  get: <T>(endpoint: string) => Promise<any>;
  post: <T>(endpoint: string, data: Record<string, unknown>) => Promise<any>;
  put: <T>(endpoint: string, data: Record<string, unknown>) => Promise<any>;
  delete: <T>(endpoint: string) => Promise<any>;
}) => {
  return new {{ $className }}(apiClient);
}; 