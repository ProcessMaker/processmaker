@extends('layouts.process-map')

@section('title')
    {{__('Process Map')}}
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
    autoSaveDelay: @json($autoSaveDelay),
    xml: @json($process->bpmn),
    isVersionsInstalled: @json($isVersionsInstalled),
    isDraft: @json($isDraft),
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

  window.ProcessMaker.EventBus.$on('modeler-start', ({ loadXML }) => {
    loadXML(window.ProcessMaker.modeler.xml);
  });
</script>
@foreach($manager->getScripts() as $script)
    <script src="{{ $script }}"></script>
@endforeach
<script src="{{ mix('js/processes/modeler/process-map.js') }}"></script>
@endsection
