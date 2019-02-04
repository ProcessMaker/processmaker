@extends('layouts.layout')

@section('title')
{{__('Export Process')}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
    @include('shared.breadcrumbs', ['routes' => [
        __('Processes') => route('processes.index'),
        __('Export') => null,
    ]])
<div class="container" id="editProcess">
    <div class="row">
        <div class="col">
            <div class="card text-center">
                <div class="card-header bg-light" align="left">
                    <h5>Export Process</h5>
                </div>
                <div class="card-body">
                    <h5 class="card-title">You are about to Export a Process</h5>
                    <p class="card-text">You will need to fix hecka stuff</p> 
                </div>
                <div class="card-footer bg-light" align="right">
                    <button type="button" class="btn btn-outline-secondary">Close</button>
    			    <button type="button" class="btn btn-secondary ml-2" >Save</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection