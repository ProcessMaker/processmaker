@extends('layouts.layout')

@section('title')
  {{__('Task')}}
@endsection

@section('content')
<div class="container" id="task">
    <task-view
        process-uid="{{$process->uid}}" instance-uid="{{$instance->uid}}" token-uid="{{$token->uid}}" form-uid="{{$token->definition['formRef']}}"
        :data="{{json_encode($data)}}" token-created="{{$token->delegate_date}}" token-completed="{{$token->finish_date}}"
        user-uid="{{$token->user->uid}}" user-name="{{$token->user->fullname}}" user-avatar="{{$token->user->avatar}}"></task-view>
</div>
@endsection

@section('js')
<script src="{{mix('js/tasks/show.js')}}"></script>
@endsection
