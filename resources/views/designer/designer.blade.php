@extends('layouts.layout')
@section('content')
    <div id="designer-container">
      <designer process-uid="{{$process->uid}}"></designer>
    </div>
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_designer')])
@endsection

@section('js')
    <script src="{{ mix('js/designer/main.js') }}"></script>
@endsection
