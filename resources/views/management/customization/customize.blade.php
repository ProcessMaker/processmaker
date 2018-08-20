@extends('layouts.layout', ['title' => __('UI Customization')])

@section('sidebar')
  @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
<div>
    <h1 class="page-title">{{__('UI Customization')}}</h1>
    <div>
        <p>Add your company logo</p>
    </div>
    <div>
        <p>Create a color scheme to customize your UI</p>
        <ui-customize></ui-customize>
    </div>
</div>



@endsection

@section('js')
    <script src="{{mix('js/management/admin/index.js')}}"></script>
@endsection