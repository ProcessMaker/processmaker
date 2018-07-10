@extends('layouts.layout')

@section('content')
<div class="container" id="start">
    <process-start process-uid="{{$process->uid}}" event="{{$event}}">
    </process-start>
</div>
@endsection

@section('js')
<script src="{{mix('js/nayra/start.js')}}"></script>
@endsection
