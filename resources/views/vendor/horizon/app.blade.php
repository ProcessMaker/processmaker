@extends('layouts.layout', ['title' => 'Queue Management']) 
@section('sidebar')
        @include('layouts.sidebar', ['sidebar'=>
Menu::get('sidebar_admin')])
@endsection
 
@section('content')
<div style="height: 0; width: 0; position: absolute; display: none;">
{!! file_get_contents(public_path('/vendor/horizon/img/sprite.svg')) !!}
</div>

<div class="container page-content" id="users-listing">
        <div class="row">
                <div class="col-sm-12">
                        <div class="row">
                                <div class="col-md-8 d-flex align-items-center col-sm-12">
                                        <h1 class="page-title">Queue Management</h1>
                                        <img src="/vendor/horizon/img/horizon.svg">
                                </div>
                                <div id="root"></div>
                        </div>

                </div>
        </div>
</div>
@endsection
 
@section('js')
<script src="{{ mix('js/admin/queues/index.js') }}"></script>
@endsection
 
@section('css')
<link rel="stylesheet" type="text/css" href="{{ mix('css/admin/queues.css') }}">
@endsection