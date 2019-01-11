@extends('layouts.layout')

@section('title')
{{__('Groups')}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
<div class="container page-content" id="listGroups">
    <h1>{{__('Auth Clients')}}</h1>
    <div id="listAuthClients">
        <auth-clients-listing ref="groupList" />
    </div>
</div>
@endsection

@section('js')
<script src="{{mix('js/admin/auth-clients/index.js')}}"></script>
@endsection