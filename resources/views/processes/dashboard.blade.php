@extends('layouts.layout')

@section('title')
	@php
	$title = __('Processes');
	$status = request()->get('status');
	if( $status === 'deleted'){
		$title = __('Process Archive');
	}
	@endphp
{{$title}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
<div class="container page-content">
    <h2>{{__('Processes Dashboard')}}</h2>
</div>
@endsection