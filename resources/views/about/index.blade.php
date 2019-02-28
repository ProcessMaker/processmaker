@extends('layouts.layout')

@section('title')
  {{__('About ProcessMaker 4')}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_designer')])
@endsection

@section('content')
  @include('shared.breadcrumbs', ['routes' => [
      __('About ProcessMaker 4') => null,
  ]])
 <div class="container">
    <div class="row">
      <div class="col-8">
        <div class="card card-body">
        <img class="about-logo" src="/img/md-blk-logo.png">
        <hr>
        <div>{{__('ProcessMaker v4.0 Beta 4')}}</div>
        <hr>
        <a href="https://docs.google.com/forms/d/e/1FAIpQLScnYje8uTACYwp3VxdRoA26OFkbfFs6kuXofqY-QXXsG-h9xA/viewform" target="_blank">{{__('Report an issue')}}  <i class="fas fa-caret-right fa-lg float-right mr-1"></i></a>
        <hr>
        <a href="https://github.com/ProcessMaker/bpm" target="_blank">{{__('Get Help')}}  <i class="fas fa-caret-right fa-lg float-right mr-1"></i></a>
        <hr>
        &copy; {{date('Y')}} - All Rights Reserved
      </div>
    </div>
  </div>
</div>
@endsection

@section('css')
<style>
.about-logo {
  max-width: 300px;
}
</style>
@endsection
