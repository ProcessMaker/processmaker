@extends('layouts.layout', ['title' => __('UI Customization')])

@section('sidebar')
    @include('sidebars.default', ['sidebar'=> $sidebar_admin])
@endsection

@section('content')
<div>
    <p>test</p>
</div>

@endsection

@section('js')
    <script src="{{mix('js/processes/tasks/index.js')}}"></script>
@endsection