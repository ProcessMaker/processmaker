@foreach ($apiClasses as $class)
import { {{ $class['className'] }} } from './{{ $class['tagLower'] }}.api';
@endforeach

export {
@foreach ($apiClasses as $class)
  {{ $class['className'] }},
@endforeach
}; 