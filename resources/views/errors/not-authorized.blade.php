@extends('layouts.minimal')

@section('title')
  {{__('Unauthorized')}}
@endsection

@section('content')
<div class="error-container">
    <div class="error-404-icon">
      <img src="/img/robot.png"/>
    </div>
    <div class="error-content">
        <h1>{{__('Unauthorized')}}</h1>
        <p>{{__('This form is assigned to someone else, so it’s not available under your account.')}}</p>
        <p>{{__('If you think this is a mistake or need help, reach out to your admin or support team.')}}</p>
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
