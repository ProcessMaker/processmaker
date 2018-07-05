@extends('layouts.layout')

@section('content')
<div class="container" id="start">
    <process-call process-uid="{{$process->uid}}" process-id="{{$processId}}">
    </process-call>
</div>
@endsection

@section('js')
<script src="{{mix('js/nayra/start.js')}}"></script>
@endsection
