@extends('layouts.layout', ['content_margin'=>''])

@section('title')
  {{__('Edit Process')}}
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
  <script src="{{mix('js/leave-warning.js')}}"></script>
  <script> 
  const breadcrumbData = [
    {
      'text':'{{__('Designer')}}',
      'url':'{{route('processes.index')}}'
    },
    {
      'text':'{{__('Processes')}}',
      'url':'{{route('processes.index')}}'
    },
    {
      'text':'{{__('Modeler')}}',
      'url':''
    },
    {
      'text':'{{$process->name}}',
      'url':''
    },
  ]
  window.ProcessMaker.modeler = {
    process: @json($process),
    xml: @json($process->bpmn),
    processName: @json($process->name),
  }
  const warnings = @json($process->warnings);
 
  window.ProcessMaker.EventBus.$on('modeler-start', ({ loadXML, addWarnings, addBreadcrumbs }) => {
    loadXML(window.ProcessMaker.modeler.xml);
    addWarnings(warnings || []);
    addBreadcrumbs(breadcrumbData || []);
  });
  </script>
    @foreach($manager->getScripts() as $script)
      <script src="{{$script}}"></script>
    @endforeach
  <script src="{{ mix('js/processes/modeler/index.js') }}"></script>
@endsection
