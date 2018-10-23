@extends('layouts.layout')

@section('content')
<div class="container" id="start">
    <request-form 
        process-id="{{$process->id}}" instance-id="{{$instance->id}}" token-id="{{$token->id}}" form-id="{{$token->definition['formRef']}}"
        start-date="{{$startDate}}" end-date="{{$endDate}}" reason="{{$reason}}"></request-form>
</div>
@endsection

@section('js')
<script src="{{mix('js/nayra/start.js')}}"></script>
@endsection
