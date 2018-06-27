@extends('layouts.layout')

@section('content')
<div class="container" id="start">
    <div class="row">
        <div class="col-sm">

            <h1>Nayra Test Start Form</h1>
            <p>@{{message}}</p>

        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{mix('js/nayra/start.js')}}"></script>
@endsection