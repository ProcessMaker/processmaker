@extends('layouts.layout')

@section('title')
    {{__('Cases Retention')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Admin') => route('admin.index'),
        __('Cases Retention') => null,
    ]])
@endsection
@section('content')
    <div class="px-3" id="cases-retention">
        <cases-retention-logs></cases-retention-logs>
    </div>
@endsection


@section('js')
    <script src="{{mix('js/admin/cases-retention/index.js')}}"></script>
@endsection
