@foreach ($hooks as $hook)
import { {{ $hook }} } from './{{ $hook }}';
@endforeach

export {
@foreach ($hooks as $hook)
  {{ $hook }},
@endforeach
}; 