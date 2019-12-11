@extends('layouts.layout')

@section('title')
    {{ __('Request') . ' #' . $request->getKey() . ' - ' .  $screen->title }}
@endsection

@section('meta')
    <meta name="request-id" content="{{ $request->getKey() }}">
@endsection


@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_request')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Requests') => route('requests.index'),
        $request->name . ' #'. $request->getKey() => route('requests.show', [$request->getKey()]),
    ]])
@endsection
@section('content')
    <div id="request" class="container d-print-block">
        <div class="row">
            <div class="col-sm-12">
                <screen-detail :row-data="config" v-bind:can-print="true">
                </screen-detail>
            </div>
        </div>
    </div>
@endsection

@section('js')
    @foreach($manager->getScripts() as $script)
        <script src="{{$script}}"></script>
    @endforeach

    <script src="{{mix('js/requests/preview.js')}}"></script>
    <script>
      new Vue({
        el: "#request",
        data() {
          return {
            data: @json($request->data),
            screenRequested: @json($screen),
            request: @json($request),
          };
        },
        computed: {
          config() {
            this.screenRequested.data = this.data;
            return this.screenRequested;
          },

        },
      });
    </script>
@endsection
