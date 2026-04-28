@extends('layouts.minimal')

@section('title')
  {{__('Unauthorized')}}
@endsection

@section('content')
<div class="error-container">
    <div class="error-404-icon">
      <img src="/img/task-is-not-assigned.png"/>
    </div>
    <div class="error-content">
        <h1>{{__('This Task is not Assigned')}}</h1>
        <p>{{__('To be able to continue the task')}}</p>
        <p>{{__('needs to be assigned.')}}</p>
    </div>
</div>
@endsection

@section('css')

<style>
    .container {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 10%;
    }
    .error-content {
        margin-top: auto;
    }
    .error-404-icon {
        text-align: center;
    }

</style>
@endsection
