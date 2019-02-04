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
@include('shared.breadcrumbs', ['routes' => [
    $title => null,
]])
<div class="container" id="editProcess">
    <div class="row">
        <div class="col">
            <div class="card card-body">
                
            </div>
        </div>
    </div>
</div>
@endsection