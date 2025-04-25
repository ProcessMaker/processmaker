@if (!empty($imports))
import {
@foreach ($imports as $import)
  {{ $import }},
@endforeach
} from '../types/types';
@endif

export class {{ $className }} {
  constructor(private apiClient: {
    head: <T>(endpoint: string) => Promise<T>;
    get: <T>(endpoint: string) => Promise<T>;
    post: <T>(endpoint: string, data: Record<string, unknown>) => Promise<T>;
    put: <T>(endpoint: string, data: Record<string, unknown>) => Promise<T>;
    delete: <T>(endpoint: string) => Promise<T>;
    patch: <T>(endpoint: string, data: Record<string, unknown>) => Promise<T>;
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
$paramType = $helper->getTypescriptType($param);
if ($paramType === 'boolean') {
    $paramValue = "params.{$paramName} ? '1' : '0'";
} elseif ($paramType === 'integer' || $paramType === 'number') {
    $paramValue = "String(params.{$paramName})";
} elseif ($paramType === 'array' || $paramType === 'string[]') {
    $paramValue = "params.{$paramName}.join(',')";
} elseif ($paramType === 'string') {
    $paramValue = "params.{$paramName}";
} else {
    $paramValue = "JSON.stringify(params.{$paramName})";
}
@endphp
      if (params.{{ $paramName }}) queryParams.append('{{ $param['name'] }}', {!! $paramValue !!}); // {{ $paramType }}
@endforeach
    }

    const queryString = queryParams.toString() ? `?${queryParams.toString()}` : '';
@endif

@if ($method['httpMethod'] === 'get' || $method['httpMethod'] === 'head')
@if (!empty($method['queryParams']))
    return this.apiClient.{{ $method['httpMethod'] }}<{!! $method['returnType'] ?: 'void' !!}>(`{{ $method['apiPath'] }}${queryString}`);
@else
    return this.apiClient.{{ $method['httpMethod'] }}<{!! $method['returnType'] ?: 'void' !!}>(`{{ $method['apiPath'] }}`);
@endif
@elseif ($method['httpMethod'] === 'post' ||$method['httpMethod'] === 'put' || $method['httpMethod'] === 'patch')
@if (!empty($method['queryParams']))
    return this.apiClient.{{$method['httpMethod']}}<{!! $method['returnType'] ?: 'void' !!}>(`{{ $method['apiPath'] }}${queryString}`, data as unknown as Record<string, unknown>);
@else
    return this.apiClient.{{$method['httpMethod']}}<{!! $method['returnType'] ?: 'void' !!}>(`{{ $method['apiPath'] }}`, data as unknown as Record<string, unknown>);
@endif
@elseif ($method['httpMethod'] === 'delete')
    return this.apiClient.delete<{!! $method['returnType'] ?: 'void' !!}>(`{{ $method['apiPath'] }}`);
@endif
  }

@endforeach
} 