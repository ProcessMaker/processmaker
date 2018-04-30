@extends('layouts.layout')
@section('content')
    <link href="{{ asset('css/vendorDesigner.css') }}" rel="stylesheet">
    <div class="pmdesigner-container" id="appDesigner">
        <toolbar></toolbar>
        <designer ref="canvas"></designer>
    </div>
@endsection

@push('scripts')
<script src="{{ asset('js/snap.svg.js') }}"></script>
<script src="{{ asset('js/AppDesigner.js') }}"></script>
@endpush

@section('sidebar')
    @include('sidebars.default')
@endsection