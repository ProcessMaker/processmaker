@extends('layouts.layout')

@section('content')
<div class="container" id="start">
    {{$startDate}}
    <request-form start-date="{{$startDate}}" end-date="{{$endDate}}" reason="{{$reason}}"></request-form>
</div>
@endsection

@section('js')
<script src="{{mix('js/nayra/start.js')}}"></script>
@endsection
