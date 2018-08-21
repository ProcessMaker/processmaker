@extends('layouts.layout', ['title' => __('UI Customization')])

@section('sidebar')
  @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
<div id="uicustomize">
    <h1 class="page-title">{{__('UI Customization')}}</h1>
    <div class="container page-content">
        <p>Add your company logo</p>
    </div>
    <div>
        <p>Create a color scheme to customize your UI</p>
        <span>
            <div>
            </div>
        </span>
        <customize-color></customize-color>
    </div>
</div>



@endsection

@section('js')
    <script src="{{mix('js/management/admin/index.js')}}"></script>
@endsection