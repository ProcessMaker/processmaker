<h5>{{ __('Microservices') }}</h5>
<ul class="list-group-flush p-0">
@foreach ($microServices as $microService)
    <li class="list-group-item">
        <h6><i class="fas fa-puzzle-piece mr-2"></i>{{ ucfirst(trans($microService['name'])) }}</h6>
        <small>
        @if (isset($microService['description']))
            <div>{{ $microService['description'] }}</div>
        @endif
        @if (isset($microService['version']))
            <div><strong>Version:</strong> {{ $microService['version'] }}</div>
        @endif
        </small>
        @if (!empty($microService['waiting']))
        <i class="fas fa-sync fa-spin text-secondary waiting"></i>
        @endif
    </li>
@endforeach
</ul>
