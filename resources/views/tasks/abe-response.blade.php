@extends('errors.base')

@section('title', __('Actions by Email'))

@section('message')
    <h1>{{ __('Actions by Email') }}</h1>
    <p>{{ $message }}</p>
@endsection

@section('buttons', '')
