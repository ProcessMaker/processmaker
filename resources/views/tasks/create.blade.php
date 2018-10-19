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
        process-uid="{{$process->uid}}" instance-uid="{{$instance->uid}}" token-uid="{{$token->uid}}" form-uid="{{$token->definition['formRef']}}"
        :data="{{json_encode($data)}}"></task-form>
</div>
@endsection

@section('js')
<script src="{{mix('js/tasks/show.js')}}"></script>
@endsection
