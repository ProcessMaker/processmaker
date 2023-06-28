<div id="map-legend" class="card map-legend-card">
  <div class="card-body map-legend-body">
    <p class="map-legend-label">
      <span class="line in-progress-line"></span>
      {{ __('In Progress') }}
    </p>
    <p class="map-legend-label map-legend-label-margin">
      <span class="line completed-line"></span>
      {{ __('Completed') }}
    </p>
  </div>
</div>

@section('css')
  <style>
    :root {
      --color-line-in-progress: #1572C2;
      --color-line-completed: #00875A;
    }

    #map-legend.map-legend-card {
      position: absolute;
      top: 70px;
      right: 35px;
    }

    .map-legend-body {
      padding-top: 0px;
      padding-bottom: 0px;
      padding-left: 5px;
    }

    .map-legend-label {
      font-weight: bold;
    }

    .map-legend-label-margin {
      margin-bottom: 1rem !important;
    }

    .line {
      width: 25px;
      height: 30px;
      margin-right: 20px;
      display: inline-block;
      border-right-width: 3px;
      transform: rotate(45deg);
    }

    .in-progress-line {
      border-right-style: dashed;
      border-right-color: var(--color-line-in-progress);
    }

    .completed-line {
      border-right-style: solid;
      border-right-color: var(--color-line-completed);
    }
  </style>
@endsection
