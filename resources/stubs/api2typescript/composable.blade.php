/* eslint-disable @typescript-eslint/no-explicit-any */
import { {{ $className }} } from '../api/{{ $tagLower }}.api';

/**
 * Hook to access ProcessMaker {{ ucfirst($tagLower) }} API
 * @param apiClient - API client with authentication handling
 * @returns {{ $className }} instance
 */
export const {{ $hookName }} = (apiClient: {
  head: <T>(endpoint: string) => Promise<any>;
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  get: <T>(endpoint: string) => Promise<any>;
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  post: <T>(endpoint: string, data: Record<string, unknown>) => Promise<any>;
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  put: <T>(endpoint: string, data: Record<string, unknown>) => Promise<any>;
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  delete: <T>(endpoint: string) => Promise<any>;
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  patch: <T>(endpoint: string, data: Record<string, unknown>) => Promise<any>;
}) => {
  return new {{ $className }}(apiClient);
};
