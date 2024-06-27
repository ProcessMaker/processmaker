@extends('layouts.layout')

@section('title')
  {{ __('Process Intelligence') }}
@endsection

@section('sidebar')
  @include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_admin')])
@endsection

@section('breadcrumbs')
  @include('shared.breadcrumbs', [
      'routes' => [
          __('Admin') => route('admin.index'),
          __('Process Intelligence') => null,
      ],
  ])
@endsection
@section('content')
  <iframe
    src="https://dev-app.workfellow.com/automatic-sign-in?token={{ $token }}"
    title="{{ __('Process Intelligence') }}"
    width="100%"
    height="600"
  ></iframe>
@endsection
