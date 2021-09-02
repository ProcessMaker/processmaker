@extends('layouts.minimal')

@section('title')
  {{__('Unauthorized - ProcessMaker')}}
@endsection

@section('content')
<div class="error-container">
    <div class="error-404-icon">
      <img src="/img/robot.png"/>
    </div>
    <div class="error-content">
      @yield('message')
    </div>
    <div class="buttons">
        <div class="row">
            <div class="col">
                <a class="btn btn-primary btn-block" href="javascript:history.back()" role="button"><i class="fas fa-backward"></i></a>
            </div>
            <div class="col">
                <a class="btn btn-primary btn-block" href="/" role="button"><i class="fas fa-home"></i></a>
            </div>
        </div>
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