@extends('layouts.layout')

@section('title')
  {{__('About')}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
 <div class="container">
    <h1>About ProcessMaker</h1>
    <div class="row">
      <div class="col-8">
        <div class="card card-body">
        <img class="about-logo" src="/img/md-blk-logo.png">
        <hr>
        <div>ProcessMaker v4.0</div>
        <hr>
        <a href="https://github.com/ProcessMaker/bpm/issues">Report an issue  <i class="fas fa-caret-right fa-lg float-right mr-1"></i></a>
        <hr>
        <a href="https://processmaker.gitbook.io/processmaker-4-community/-LPblkrcFWowWJ6HZdhC/" target="_blank">Get Help  <i class="fas fa-caret-right fa-lg float-right mr-1"></i></a>
        <hr>
      </div>
    </div>
      <div class="col-4">
        <div class="card card-body">
        Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
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
