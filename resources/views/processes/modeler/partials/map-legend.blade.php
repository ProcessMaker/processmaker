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
    <p class="map-legend-label map-legend-label-margin">
      <span class="line idle-line"></span>
      {{ __('Pending / Not Executed') }}
    </p>
  </div>
</div>

@section('css')
  <style>
    /* See node_modules/@processmaker/modeler/src/components/highlightColors.js */
    :root {
      --color-line-in-progress: #3FA6FF;
      --color-line-completed: #00BA7C;
      --color-line-idle: #CCCCCC;
    }

    #map-legend.map-legend-card {
      position: absolute;
      top: 30px;
      right: 30px;
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
      border-right-style: solid;
      border-right-color: var(--color-line-in-progress);
    }

    .completed-line {
      border-right-style: solid;
      border-right-color: var(--color-line-completed);
    }
    .idle-line {
      border-right-style: solid;
      border-right-color: var(--color-line-idle);
    }

  </style>
@endsection
