@extends('layouts.minimal')

@section('title')
  {{__('Unauthorized - ProcessMaker')}}
@endsection

@section('content')
<div class="error-container">
    <div class="error-404-icon">
      <img width="281" height="280" src="/images/broken-link.svg" alt="broken-link" />
    </div>
    <div class="error-content">
      <h1>{{__('No permissions to access this content')}}</h1>
      <p>{{__(
        'If you believe this is an error, please contact the system administrator or support team for assistance.'
      )}}</p>
    </div>
    <div class="buttons">
        <div class="row">
            <div class="col text-center">
                <a class="btn btn-primary" aria-label="{{__('Retry')}}" href="/" role="button">Retry</a>
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


