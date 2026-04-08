@extends('layouts.layout')

@section('title')
    {{ __('Request') . ' #' . $request->getKey() . ' - ' .  $screen->title }}
@endsection

@section('meta')
    <meta name="request-id" content="{{ $request->getKey() }}">
@endsection
@section('css')
    @include('shared.prospect-process-shell-styles')
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
    @php($isProspectProcess = \Illuminate\Support\Str::contains(\Illuminate\Support\Str::lower($request->process->name ?? ''), 'prospect'))
    <div id="request" class="container d-print-block {{ $isProspectProcess ? 'prospect-process-shell' : '' }}">
        <div class="row">
            <div class="col-sm-12">
                <screen-detail :row-data="config" v-bind:can-print="true" :timeout-on-load="true">
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
            data: @json($data),
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
