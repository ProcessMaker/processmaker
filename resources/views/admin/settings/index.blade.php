@extends('layouts.layoutnextvite')

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
    <script>
      window.temporal = window.temporal || {};
      window.temporal.packages = @json(\App::make(ProcessMaker\Managers\PackageManager::class)->listPackages());
      window.packages = window.temporal.packages;
    </script>
    @vite(['resources/js/admin/settings/loaderSettings.js'])

    @if (hasPackage('package-email-start-event'))
    <script type="module" src="{{ mix('js/email-listener.js', 'vendor/processmaker/packages/package-email-start-event') }}"></script>
    @endif

    @vite(['resources/js/admin/settings/index.js'])

    @if($errors->has('error'))
        <script>
            window.addEventListener('load', function () {
              window.ProcessMaker.alert(@json($errors->first('error')), 'danger');
            });
        </script>
    @endif
     @if($errors->has('message'))
        <script>
            window.addEventListener('load', function () {
              window.ProcessMaker.alert(@json($errors->first('message')), 'success');
            });
        </script>
    @endif
@endsection
