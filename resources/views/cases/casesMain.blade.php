@extends('layouts.layoutnextvite',['content_margin' => '', 'overflow-auto' => ''])

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
  {{-- temporal must exist before loaderCases (Vite modules run after deferred classic scripts) --}}
  <script>
    window.temporal = {};
    window.temporal.user = @json($currentUser);
    window.temporal.packages = @json(\App::make(ProcessMaker\Managers\PackageManager::class)->listPackages());
  </script>
  @vite(['resources/jscomposition/cases/casesMain/loaderCasesMain.js'])
  @foreach(GlobalScripts::getScripts() as $script)
    <script defer src="{{$script}}"></script>
  @endforeach
  @foreach($manager->getScripts() as $script)
    <script defer src="{{$script}}"></script>
  @endforeach
  @vite(['resources/jscomposition/cases/casesMain/casesMain.js'])
@endsection

@section('css')
@endsection
