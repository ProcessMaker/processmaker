@foreach ($interfaces as $interface)
{!! $interface !!}

@endforeach

/*export interface PaginatedResponse<T> {
  data: T[];
  meta: Metadata;
}*/

@foreach ($queryParamInterfaces as $interface)
{!! $interface !!}

@endforeach
