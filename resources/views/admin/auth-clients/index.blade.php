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
    <div class="container page-content" id="listAuthClients">
        <div class="row align-items-center">
            <div class="col-8">
                
            </div>
            <div class="col-4" align="right">
                <button class="btn btn-secondary" type="button" class="btn btn-secondary" data-toggle="modal"
                    data-target="#createAuthClient">
                    <i class="fas fa-plus"></i>
                    {{__('Auth Client')}}</a>
                </button>
            </div>
        </div>
        <auth-clients-listing ref="authClientList"/>
    </div>

    <div class="modal" tabindex="-1" role="dialog" id="createAuthClient">
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
                    <div class="form-group">
                        {!!Form::label('value', __('Value'))!!}
                        {!!Form::text('value', null, ['class'=> 'form-control', 'v-model'=> 'authClient.secret'])!!}
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
@endsection

@section('js')
    <script src="{{mix('js/admin/auth-clients/index.js')}}"></script>

    <script>
        new Vue({
            el: '#createAuthClient',
            data: {
                authClient: {
                    id: null,
                    name: "",
                    redirect: "",
                    secret: "",
                },
                errors: {
                    name: null,
                    redirect: null,
                },
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
                        this.$refs.createEditAuthClient.hide()
                        this.$refs.authClientList.fetch()
                        this.resetValues();
                        this.loading = false
                    }).catch(error => {
                        this.errors = error.response.data.errors
                    });
                },
                resetValues() {
                    this.errors = { name: null, redirect: null }
                }
            },
        })
    </script>
@endsection
