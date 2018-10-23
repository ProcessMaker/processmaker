@extends('layouts.layout')

@section('title')
  {{__('Designer')}}
@endsection

@section('content')
    <div id="designer-container">
      <designer process-id="{{$process->id}}" process-title="{{ $title }}"></designer>
    </div>
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_designer')])
@endsection

@section('js')
    <script src="{{ mix('js/designer/main.js') }}"></script>
@endsection
