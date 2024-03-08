@extends('layouts.layout')

@section('title')
    {{__('Settings')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Admin') => route('admin.index'),
        __('Settings') => null,
    ]])
@endsection
@section('content')
    <div id="settings">
        <settings-main ref="settings-groups"></settings-main>
    </div>
    @isset($addons)
        @foreach ($addons as $addon)
            {!! $addon['content'] ?? '' !!}
        @endforeach
    @endisset
@endsection

@section('js')
    <script src="{{mix('js/admin/settings/index.js')}}"></script>
    @if($errors->has('error'))
        <script>
            window.ProcessMaker.alert("{{ $errors->first('error') }}", 'danger');
        </script>
    @endif
     @if($errors->has('message'))
        <script>
            window.ProcessMaker.alert("{{ $message->first('message') }}", 'success');
        </script>
    @endif
@endsection
