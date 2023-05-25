@extends('layouts.process-map')

@section('title')
  {{ __('Process Map') }}
@endsection

@section('content')
  <div id="modeler-app"></div>
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
      process: @json($process),
      processName: @json($process->name),
      xml: @json($bpmn),
      configurables: [],
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
  <script src="{{ mix('js/processes/modeler/process-map.js') }}"></script>
@endsection
