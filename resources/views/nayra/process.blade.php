@extends('layouts.layout')

@section('content')
<div class="container" id="start">
    <div class="card">
        <div class="card-body">
            <h1>{{$process->name}}</h1>
            <p>{{$process->description}}</p>
            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <p class="font-weight-bold text-right">Status:</p>
                </div>
                <div class="col-sm-6 col-md-3">
                    <b-badge variant="{{$process->status===\ProcessMaker\Model\Process::STATUS_ACTIVE ? 'primary' : 'secondary'}}">{{$process->status}}</b-badge>
                </div>

                <div class="col-sm-6 col-md-3">
                    <p class="font-weight-bold text-right">Created:</p>
                </div>
                <div class="col-sm-6 col-md-3">
                    {{$process->created_at}}
                </div>

                <div class="col-sm-6 col-md-3">
                    <p class="font-weight-bold text-right">Created by:</p>
                </div>
                <div class="col-sm-6 col-md-3">
                    {{$process->creator->getFullName()}}
                </div>

                <div class="col-sm-6 col-md-3">
                    <p class="font-weight-bold text-right">Last Updated:</p>
                </div>
                <div class="col-sm-6 col-md-3">
                    {{$process->updated_at}}
                </div>

                <div class="col-sm-6 col-md-3">
                    <p class="font-weight-bold text-right">Category:</p>
                </div>
                <div class="col-sm-6 col-md-3">
                    {{$process->category->name}}
                </div>
            </div>
            <process-call process-id="{{$process->id}}" process-id="{{$processId}}">
            </process-call>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{mix('js/nayra/start.js')}}"></script>
@endsection
@section('sidebar')
  @include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_request')])
@endsection
