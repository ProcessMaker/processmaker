@extends('layouts.layout', ['title' => __('Tasks Management')])

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
    
@endsection

@section('js')
    <script src="{{mix('js/processes/tasks/index.js')}}"></script>
@endsection
