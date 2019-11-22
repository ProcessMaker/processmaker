@extends('layouts.layout', ['content_margin'=>''])

@section('title')
    {{__('Edit Screen')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route('processes.index'),
        __('Screens') => route('screens.index'),
        $screen->title => null,
    ]])
@endsection

@section('content')
    <div id="screen-container" style="display: contents !important">
        @if($screen['type']==='FORM (ADVANCED)')
        <advanced-screen-builder :screen="{{$screen}}"
                        :permission="{{ \Auth::user()->hasPermissionsFor('screens') }}">
        </advanced-screen-builder>
        @else
        <screen-builder :screen="{{$screen}}"
                        :permission="{{ \Auth::user()->hasPermissionsFor('screens') }}">
        </screen-builder>
        @endif
    </div>
@endsection

@section('js')
    <script src="{{mix('js/leave-warning.js')}}"></script>
    @foreach($manager->getScripts() as $script)
        <script src="{{$script}}"></script>
    @endforeach
    <script src="{{mix('js/processes/screen-builder/main.js')}}"></script>
@endsection
