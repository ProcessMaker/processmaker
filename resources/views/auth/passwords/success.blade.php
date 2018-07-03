@extends('layouts.minimal')

@section('content')
<div align="center">
  <div class="formContainer">
    <img src="/img/md-blue-logo.png">
    <div class="form" align="center">
      <div class="form-group">
        <small>
          <strong>{{__('Success!')}}</strong> {{__('Your password has been updated.')}}
        </small>
      </div>
      <div class="form-group">
        <a href="{{route('logout')}}" class="btn btn-success">{{__('Return to Login')}}</a>
      </div>
    </div>
  </div>
</div>

@endsection
@section('css')
<style media="screen">
  .formContainer {
    width: 400px;
  }

  .formContainer .form {
    margin-top: 114px;
    text-align: center
  }

  .formContainer a {
    margin-top: 14px;
  }
</style>
@endsection
