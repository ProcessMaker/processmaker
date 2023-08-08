@extends('layouts.process-map')

@section('title')
  {{ __('Process Map') }}
@endsection

@section('content')
  <div id="modeler-app"></div>
  @include('processes.modeler.partials.map-legend')
@endsection

@section('css')
  <style>
    div.main {
      position: relative;
    }

    #modeler-app {
      position: relative;
      width: 100%;
      max-width: 100%;
      height: 100%;
      max-height: 100%;
    }
  </style>
@endsection

@section('js')
  <script>
    const breadcrumbData = [];
    window.ProcessMaker.modeler = {
      xml: @json($bpmn),
      configurables: [],
      requestCompletedNodes: @json($requestCompletedNodes),
      requestInProgressNodes: @json($requestInProgressNodes),
      requestIdleNodes: @json($requestIdleNodes),
      requestId: @json($requestId),
    }

    window.ProcessMaker.EventBus.$on('modeler-start', ({
      loadXML
    }) => {
      loadXML(window.ProcessMaker.modeler.xml);
    });
  </script>
  @foreach ($manager->getScripts() as $script)
    <script src="{{ $script }}"></script>
  @endforeach
  @vite('resources/js/processes/modeler/process-map.js')
@endsection
