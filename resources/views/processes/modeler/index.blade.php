@extends('layouts.layout', ['content_margin'=>''])

@section('title')
  {{__('Edit Process')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
    <div class="sr-only">{{ __('A mouse and keyboard are required to use the modeler.') }}</div>
    <div id="modeler-app">
    </div>
@endsection

@section('meta')
<meta name="anonymous-user-id" content="{{ app(\ProcessMaker\Models\AnonymousUser::class)->id }}">
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
    autoSaveDelay: @json($autoSaveDelay),
    xml: @json($process->bpmn),
    isVersionsInstalled: @json($isVersionsInstalled),
    processName: @json($process->name),
    signalPermissions: @json($signalPermissions),
    // list of toggles in assignment rules
    configurables: ['SECTION_TITLE:ASSIGNMENT_OPTIONS', 'SELF_SERVICE', 'LOCK_TASK_ASSIGNMENT', 'ALLOW_REASSIGNMENT'],
    // list of items for assignment Types dropdown list
    assignmentTypes: [
      { value: "user_group", label: "Users / Groups" },
      { value: "previous_task_assignee", label: "Previous Task Assignee" },
      { value: "requester", label: "Request Starter" },
      { value: "process_variable", label: "Process Variable" },
      { value: "rule_expression", label: "Rule Expression" },
      { value: "process_manager", label: "Process Manager" },
    ],
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
