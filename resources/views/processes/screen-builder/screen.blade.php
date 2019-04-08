@extends('layouts.layout')

@section('title')
    {{__('Edit Screen')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Processes') => route('processes.index'),
        __('Screens') => route('screens.index'),
        __('Edit') . " " . $screen->title => null,
    ]])
@endsection

@section('content')
    <div id="screen-container" style="display: contents !important">
        <screen-builder :screen="{{$screen}}"
                        :permission="{{ \Auth::user()->hasPermissionsFor('screens') }}">
        </screen-builder>
    </div>
@endsection

@section('js')
    <script>
      new Vue({
        created() {
          window.addEventListener('beforeunload', this.handler)
        },
        methods: {
          handler: function handler(event) {
            let confirmationMessage = __('Are you sure you want to leave?');

            event.returnValue = confirmationMessage;     // Gecko, Trident, Chrome 34+
            return confirmationMessage;
          }
        }
      });
    </script>
    @foreach($manager->getScripts() as $script)
        <script src="{{$script}}"></script>
    @endforeach
    <script src="{{mix('js/processes/screen-builder/main.js')}}"></script>
@endsection

@section('css')
    <style>
        ol.breadcrumb {
            margin-bottom: 0;
            border-bottom: 0;
        }
    </style>
@endsection
