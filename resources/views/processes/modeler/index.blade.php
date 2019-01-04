@extends('layouts.layout')

@section('title')
  {{__('Process')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection


@section('content')
    <div id="modeler-app">
    </div>
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
  window.ProcessMaker.modeler = {
    process: @json($process),
    xml: @json($process->bpmn)
  }
  ProcessMaker.EventBus.$on('modeler-start', function(modeler) {
    modeler.loadXML(window.ProcessMaker.modeler.xml);
  });
  </script>
    @foreach($manager->getScripts() as $script)
      <script src="{{$script}}"></script>
    @endforeach
  <script src="{{ mix('js/processes/modeler/index.js') }}"></script>
@endsection
