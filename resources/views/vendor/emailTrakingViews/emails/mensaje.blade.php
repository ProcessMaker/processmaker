@extends('emailTrakingViews::emails/mensaje_layout')
@section('title')
    Message from {{config('mail-tracker.name')}}
@endsection

@section('preheader')
    Message from {{config('mail-tracker.name')}} <br>
@endsection
@section('nombre_destinatario')
    {{ $data['name'] }}
@endsection
@section('mensaje')
    <h3>Static Email Title</h3>
    <p>
        Static Email Content
    </p>
   {{ $data['message'] }}
@endsection
@section('href_call_to_action')
    {{env('APP_URL')}}
@endsection
@section('txt_call_to_action')
    Call To Action
@endsection
@section('txt_extra')
    This email comes from <a href="{{env('APP_URL')}}" style="text-decoration:none; color:#4b679d;">{{config('mail-tracker.name')}}</a>
@endsection
@section('saludo_final')
    Regards
@endsection
