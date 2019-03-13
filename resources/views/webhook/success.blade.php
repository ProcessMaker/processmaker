@extends('layouts.minimal')
@section('content')

    <div align="container">
        <div align="center" class="p-5">
            <img src={{asset(env('LOGIN_LOGO_PATH', '/img/processmaker_login.png'))}}>
        </div>

        <div class="row">
            <div class="col-md-6 offset-md-3">
                <div class="card card-body">
                    <h2 class="text-center">{{__('Request Received!')}}</h2>
                    <div class="card-text text-center mt-4">
                        <i class="far fa-check-circle" style="font-size:80px; color:green;"></i>
                    </div>
                    <p class="card-text text-center mt-4">
                        {{__('ProcessMaker 4 is busy processing your request.')}} <br/>
                        {{__('You can close this page.')}}
                    </p>
                </div>

            </div>


        </div>
    </div>
@endsection
@section('css')
    <style media="screen">
        .formContainer {
            width:504px;
        }
        .formContainer .form {
            margin-top:85px;
            text-align: left
        }
    </style>

@endsection
