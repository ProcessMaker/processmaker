export interface ApiResponse<T> {
  data?: T;
  status?: number;
  statusText?: string;
  error?: string;
  message?: string;
} 