@extends('layouts.layout')

@section('content')
<div class="container" id="start" v-cloak>
    <div class="row">
        <div class="col-sm">
        <h1>Test Vacation Request Entry Form</h1>
        <p>@{{message}}</p>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{mix('js/nayra/start.js')}}"></script>
@endsection