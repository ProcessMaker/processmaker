@extends('layouts.layout')

@section('title')
{{__('Designer')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route('designer.index'),
    ]])
@endsection
@section('content')

<div id="new-designer" class="p-3 page-content mb-0 bg-white">
    <welcome-designer></welcome-designer>
    <div class="card card-body border-0">
        <div class="row">
            <div class="col-6">
                <div class="row">
                    <div class="col-12">
                        <assets :permission="{{ \Auth::user()->hasPermissionsFor('processes', 'scripts', 'screens', 'data-sources', 'decision_tables') }}" />
                    </div>
                    <div class="col-12">
                        <my-project 
                            status="{{ $listConfig->status }}"
                            project="{{ $listConfig->hasPackage }}"
                        />
                    </div>
                </div>
            </div>
            <div class="col-6">
                <recent-assets
                    :current-user-id="{{ \Auth::user()->id }}"
                    project="{{ $listConfig->hasPackage }}"
                    :permission="{{ \Auth::user()->hasPermissionsFor(
                        'processes',
                        'process-templates',
                        'pm-blocks',
                        'data-sources',
                        'projects',
                        'screens',
                        'scripts',
                        'decision_tables',
                        'flow_genies'
                    ) }}"
                    is-documenter-installed="{{\ProcessMaker\PackageHelper::isPmPackageProcessDocumenterInstalled()}}"
                />
            </div>
        </div>
     </div>
</div>
@endsection

@section('js')
<script>
    window.Processmaker.user = @json($currentUser);
</script>
<script src="{{mix('js/processes/newDesigner.js')}}"></script>
@endsection

