@extends('layouts.layout')

@section('title')
  {{__('About ProcessMaker')}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_about')])
@endsection

@section('content')
  @include('shared.breadcrumbs', ['routes' => [
      __('About ProcessMaker') => null,
  ]])
 <div class="container">
    <div class="row">
      <div class="col-8">
        <div class="card card-body">
        <img class="about-logo" src="/img/md-blk-logo.png">
        <hr>
        <div>{{__('ProcessMaker 4')}} Beta 8.1</div>
        <hr>
        <a href="https://docs.google.com/forms/d/e/1FAIpQLScnYje8uTACYwp3VxdRoA26OFkbfFs6kuXofqY-QXXsG-h9xA/viewform" target="_blank">{{__('Report an issue')}}  <i class="fas fa-caret-right fa-lg float-right mr-1"></i></a>
        <hr>
        <a href="https://github.com/ProcessMaker/bpm" target="_blank">{{__('Get Help')}}  <i class="fas fa-caret-right fa-lg float-right mr-1"></i></a>
        <hr>
        <a href="https://processmaker.gitbook.io/processmaker/" target="_blank">{{__('Documentation')}}  <i class="fas fa-caret-right fa-lg float-right mr-1"></i></a>
        <hr>
        @if ($packages)
        <h5>{{ __('Packages Installed') }}</h5>
        <ul class="list-group-flush p-0">
            @foreach ($packages as $package)
            <li class="list-group-item">
                <h6><i class="fas fa-puzzle-piece mr-2"></i>{{ ucfirst(trans($package->name)) }}</h6>
                <small>
                  <div>{{ $package->description }}</div>
                  @if (isset($package->version))
                    <div><strong>Version:</strong> {{ $package->version }}</div>
                  @endif
                </small>
            </li>
          @endforeach
        </ul>
        @endif
        &copy; {{date('Y')}} - {{__('All Rights Reserved')}}
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
