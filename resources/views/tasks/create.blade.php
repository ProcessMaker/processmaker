@extends('layouts.layout')

@section('title')
  {{__('Add a Task')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_task')])
@endsection

@section('content')
<div id="task">
    <task-form
        process-id="{{$process->id}}" instance-id="{{$instance->id}}" token-id="{{$token->id}}" form-id="{{$token->definition['formRef']}}"
        :data="{{json_encode($data)}}"></task-form>
</div>
@endsection

@section('js')
<script src="{{mix('js/tasks/show.js')}}"></script>
@endsection
