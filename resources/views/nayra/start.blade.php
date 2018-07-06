@extends('layouts.layout')

@section('content')
<div class="container" id="start">
    <process-start process-uid="{{$process->uid}}" event="{{$event}}">
    </process-start>
    <div class="alert alert-success" v-for="message in messages">@{{message.message}}: <a v-bind:href="message.url" target="_blank">@{{message.uid}}</a></div>
</div>
@endsection

@section('js')
<script src="{{mix('js/nayra/start.js')}}"></script>
@endsection
