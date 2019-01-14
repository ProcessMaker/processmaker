@extends('layouts.layout')

@section('title')
{{__('Auth Clients')}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
<div class="container page-content" id="listAuthClients">
    <div class="row align-items-center">
        <div class="col-8">
            <h1>{{__('Auth Clients')}}</h1>
        </div>
        <div class="col-4" align="right">
            <b-button @click="create">
                <i class="fas fa-plus"></i>
                {{__('Auth Client')}}</a>
            </b-button>
        </div>
    </div>
    <div>
        <b-modal ref="createEditAuthClient" :title="modalTitle" @ok="save">
            <div class="form-group">
                <label for="authClientName">Name</label>
                <b-form-input id="authClientName" v-model="authClient.name" type="text" placeholder="Enter a name for this auth client"></b-form-input>
            </div>
            <div class="form-group">
                <label for="authClientName">Redirect URL</label>
                <b-form-input v-model="authClient.redirect" type="text" placeholder="Enter the URL to redirect to"></b-form-input>
            </div>
            <div v-if="authClient.secret != ''" class="form-group">
                <label for="authClientName">Client Secret</label>
                @{{ authClient.secret }}
            </div>
        </b-modal>
        <auth-clients-listing ref="authClientList" @edit="edit" />
    </div>
</div>
@endsection

@section('js')
<script src="{{mix('js/admin/auth-clients/index.js')}}"></script>
@endsection