@extends('layouts.layout')

@section('content')
<div class="container" id="start">
    <validate-form 
        process-uid="{{$process->uid}}" instance-uid="{{$instance->uid}}" token-uid="{{$token->uid}}" form-uid="{{$token->definition['formRef']}}"
        start-date="{{$startDate}}" end-date="{{$endDate}}" reason="{{$reason}}" approved="{{$approved}}"></validate-form>
</div>
@endsection

@section('js')
<script src="{{mix('js/nayra/start.js')}}"></script>
@endsection
