@extends('layouts.layoutnext',['content_margin' => '', 'overflow-auto' => ''])

@section('title')
  {{ __('Cases') }}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_cases')])
@endsection

@section('content')
<div id="cases-main"></div>
@endsection

@section('js')
  <script src="{{ mix('js/manifest.js') }}"></script>
  <script src="{{ mix('js/vue-vendor.js') }}"></script>
  <script src="{{ mix('js/fortawesome-vendor.js') }}"></script>
  <script src="{{ mix('js/bootstrap-vendor.js') }}"></script>
  
  <script>
    const currentUser = @json($currentUser);
    const screenBuilderScripts = @json($manager->getScripts());
    window.packages = @json(\App::make(ProcessMaker\Managers\PackageManager::class)->listPackages());
  </script>

  <script src="{{mix('js/composition/cases/casesMain/loader.js')}}"></script>

  @foreach(GlobalScripts::getScripts() as $script)
    <script src="{{$script}}"></script>
  @endforeach

  <script src="{{mix('js/composition/cases/casesMain/main.js')}}"></script>
@endsection

@section('css')
@endsection
