@extends('layouts.minimal')

@section('title')
{{__('Forgot Your Password?')}}
@endsection

@section('content')

<div align="container">
  <div align="center" class="p-5">
    @component('components.logo')
    @endcomponent
    <h3>{{__('Forgot Your Password?')}}</h3>
    <p>{{__("Enter your email address and we'll send you a reset link.")}}</p>
  </div>

  <div class="row">
    <div class="col-md-6 offset-md-3">
      <div class="card card-body">
		<form method="POST" class="form" action="{{ route('password.email') }}">
			@if (session('status'))
			<div class="alert alert-success">
				{{ session('status') }}
			</div>
			@endif
			@csrf
			<div class="form-group">
				<label for="email">{{ __('Email Address') }}</label>
				<input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ old('email') }}" required>
				@if ($errors->has('email'))
				<span class="invalid-feedback" role="alert">
				<strong>{{ $errors->first('email') }}</strong>
				</span>
				@endif
			</div>
			<div class="form-group">
				<button type="submit" class="btn btn-success btn-block">{{ __('Request Reset Link') }}</button>
			</div>
			<div class="form-group">
				<a href="/login">{{__("Back to Login")}}</a>
			</div>
		</form>
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
    margin-top: 50px;
    text-align: left;
  }

  .formContainer h3 {
    margin-top: 52px;
    font-size: 18px;
    font-weight: 600;
  }

  .formContainer button {
    margin-top: 6px;
  }
</style>
@endsection
