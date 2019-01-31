@extends('layouts.layout')

@section('title')
{{__('Auth Clients')}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
    @include('shared.breadcrumbs', ['routes' => [
        __('Admin') => route('admin.index'),
        __('Auth Clients') => null,
    ]])
    <div id="authClients">

        <div class="modal" role="dialog" ref="createEditAuthClient" id="createEditAuthClient">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{__('Create Environment Variable')}}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            {!!Form::label('name', __('Name'))!!}
                            {!!Form::text('name', null, ['class'=> 'form-control', 'v-model'=> 'authClient.name',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.name}'])!!}
                            <small class="form-text text-muted">{{ __('Name must be distinct') }}</small>
                            <div class="invalid-feedback" v-if="errors.name">@{{ errors.name[0] }}</div>
                        </div>
                        <div class="form-group">
                            {!!Form::label('redirect', __('Redirect URL'))!!}
                            {!!Form::text('redirect', null, ['class'=> 'form-control', 'v-model'=> 'authClient.redirect',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.redirect}','rows'=>3])!!}
                            <div class="invalid-feedback" v-if="errors.redirect">@{{ errors.redirect[0] }}</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary"
                                data-dismiss="modal">{{__('Close')}}</button>
                        <button type="button" class="btn btn-secondary ml-2" @click="save">{{__('Save')}}</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="container page-content">
            <div class="row">
                <div class="col" align="right">
                    <button class="btn btn-secondary" type="button" class="btn btn-secondary" data-toggle="modal" data-target="#createEditAuthClient">
                        <i class="fas fa-plus"></i>
                        {{__('Auth Client')}}</a>
                    </button>
                </div>
            </div>
            <auth-clients-listing ref="authClientList" @edit="edit"/>
        </div>

    </div>
@endsection

@section('js')
    <script src="{{mix('js/admin/auth-clients/index.js')}}"></script>

    <script>
        new Vue({
            el: '#authClients',
            data: {
                authClient: null,
                errors: null,
            },
            beforeMount() {
                this.resetValues();
            },
            mounted() {
                $('#createEditAuthClient').on('hidden.bs.modal', () => {
                    this.resetValues();
                });
            },
            methods: {
                save() {
                    event.preventDefault()
                    this.loading = true
                    let method = 'POST'
                    let url = '/oauth/clients'
                    if (this.authClient.id) {
                        // Do an update
                        method = 'PUT',
                        url = url + '/' + this.authClient.id
                    }
                    ProcessMaker.apiClient({
                        method,
                        url,
                        baseURL: '/',
                        data: this.authClient,
                    }).then(response => {
                        $('#createEditAuthClient').modal('hide')
                        this.$refs.authClientList.fetch()
                        this.loading = false
                        ProcessMaker.alert("Auth Client Updated", "success")
                    }).catch(error => {
                        this.errors = error.response.data.errors;
                    });
                },
                resetValues() {
                    this.authClient = {
                        id: null,
                        name: "",
                        redirect: "",
                        secret: ""
                    };
                    this.errors = {
                        name: null,
                        redirect: null
                    }
                },
                edit(item) {
                    this.authClient = item
                    $('#createEditAuthClient').modal('show');
                }
            },
        })
    </script>
@endsection
