@extends('layouts.layout')

@section('content')
<div class="container" id="start">
    <approve-form 
        process-id="{{$process->id}}" instance-id="{{$instance->id}}" token-id="{{$token->id}}" form-id="{{$token->definition['screenRef']}}"
        start-date="{{$startDate}}" end-date="{{$endDate}}" reason="{{$reason}}"></approve-form>
</div>
@endsection

@section('js')
<script src="{{mix('js/nayra/start.js')}}"></script>
@endsection
