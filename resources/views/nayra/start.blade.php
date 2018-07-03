@extends('layouts.layout')

@section('content')
<div class="container" id="start">
    <request-form process-uid="{{$process->uid}}" event="{{$event}}">

    </request-form>
</div>
@endsection

@section('js')
<script src="{{mix('js/nayra/start.js')}}"></script>
@endsection