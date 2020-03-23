@extends('layouts.layout', ['content_margin'=>''])

@section('title')
    {{__('Print Process')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
    <div id="printable-view">
    </div>
@endsection

@section('css')
    <style>
    </style>
@endsection

@section('js')
    <script>
      window.ProcessMaker.modeler = {
        processName: @json($process->name),
        updatedAt: `{{$process->updated_at}}`,
        author: @json($process->user->username),
        svg:  @json($process->svg),
        bpmn: @json($process->bpmn),
      }
    </script>

    <script src="{{ mix('js/processes/modeler/print/index.js') }}"></script>

    <script>
           const diagramContainer = document.getElementById('diagramContainer');
           const diagram = document.getElementById('v-8');
           const {width, height} = diagram.getBBox();
           const paddingMultiplier = 1.20;
           const viewBox = `0 0 ${width * paddingMultiplier} ${height * paddingMultiplier}`;
           diagram.setAttribute('viewBox', viewBox);
    </script>
@endsection
