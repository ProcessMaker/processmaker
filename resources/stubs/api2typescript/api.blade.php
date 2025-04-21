@if (!empty($imports))
import {
@foreach ($imports as $import)
  {{ $import }},
@endforeach
} from '../types/types';
@endif

export class {{ $className }} {
  constructor(private apiClient: {
    get: <T>(endpoint: string) => Promise<T>;
    post: <T>(endpoint: string, data: Record<string, unknown>) => Promise<T>;
    put: <T>(endpoint: string, data: Record<string, unknown>) => Promise<T>;
    delete: <T>(endpoint: string) => Promise<T>;
  }) {}

@foreach ($methods as $method)
  /**
   * {{ $method['summary'] }}
   */
  {!! $method['methodName'] !!}({!! implode(', ', $method['paramList']) !!}): Promise<{!! $method['returnType'] ?: 'void' !!}> {
@if (!empty($method['queryParams']))
    const queryParams = new URLSearchParams();
    
    if (params) {
@foreach ($method['queryParams'] as $param)
@php
$paramName = Illuminate\Support\Str::camel($param['name']);
$paramType = $param['schema']['type'] ?? 'string';
$paramValue = ($paramType === 'integer' || $paramType === 'number') ? "params.{$paramName}.toString()" : "params.{$paramName}";
@endphp
      if (params.{{ $paramName }}) queryParams.append('{{ $param['name'] }}', {{ $paramValue }});
@endforeach
    }

    const queryString = queryParams.toString() ? `?${queryParams.toString()}` : '';
@endif

@if ($method['httpMethod'] === 'get' || $method['httpMethod'] === 'head')
@if (!empty($method['queryParams']))
    return this.apiClient.get<{!! $method['returnType'] ?: 'void' !!}>(`{{ $method['apiPath'] }}${queryString}`);
@else
    return this.apiClient.get<{!! $method['returnType'] ?: 'void' !!}>(`{{ $method['apiPath'] }}`);
@endif
@elseif ($method['httpMethod'] === 'post')
@if (!empty($method['queryParams']))
    return this.apiClient.post<{!! $method['returnType'] ?: 'void' !!}>(`{{ $method['apiPath'] }}${queryString}`, data as unknown as Record<string, unknown>);
@else
    return this.apiClient.post<{!! $method['returnType'] ?: 'void' !!}>(`{{ $method['apiPath'] }}`, data as unknown as Record<string, unknown>);
@endif
@elseif ($method['httpMethod'] === 'put')
@if (!empty($method['queryParams']))
    return this.apiClient.put<{!! $method['returnType'] ?: 'void' !!}>(`{{ $method['apiPath'] }}${queryString}`, data as unknown as Record<string, unknown>);
@else
    return this.apiClient.put<{!! $method['returnType'] ?: 'void' !!}>(`{{ $method['apiPath'] }}`, data as unknown as Record<string, unknown>);
@endif
@elseif ($method['httpMethod'] === 'delete')
    return this.apiClient.delete<{!! $method['returnType'] ?: 'void' !!}>(`{{ $method['apiPath'] }}`);
@endif
  }

@endforeach
} 