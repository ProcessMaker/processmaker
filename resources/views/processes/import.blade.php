@extends('layouts.layout')

@section('title')
{{__('Import Process')}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
    @include('shared.breadcrumbs', ['routes' => [
        __('Processes') => route('processes.index'),
        __('Import') => null,
    ]])
<div class="container" id="importProcess">
    <div class="row">
        <div class="col">
            <div class="card text-center">
                <div class="card-header bg-light" align="left">
                    <h5>{{__('Import Process')}}</h5>
                </div>
                <div class="card-body">
                    <h5 class="card-title">{{__('You are about to import a Process')}}</h5>
                    <p class="card-text">{{__('User assignments and sensitive')}} <a href="environment-variables" >{{__('environment variables')}}</a> {{__('will not be imported.')}}</p> 
                    <input type="file" ref="file" class="d-none" @change="handleFile">
                    <button @click="$refs.file.click()" class="btn btn-secondary ml-2">
                        <i class="fas fa-upload"></i>
                        {{__('Browse')}}
                    </button>
                </div>
                <div class="card-footer bg-light" align="right">
                    <button type="button" class="btn btn-outline-secondary" @click="onCancel">{{__('Cancel')}}</button>
    			    <button type="button" class="btn btn-secondary ml-2" @click="importFile" :disabled="uploaded == false">{{__('Import')}}</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
    <script>
        new Vue({
            el: '#importProcess',
            data: {
                file: '',
                uploaded: false
            },
            methods: {
                handleFile(e) {
                    this.file = this.$refs.file.files[0];
                    this.uploaded = true
                    ProcessMaker.alert('{{__('Process successfully added')}}', 'success')
                },
                onCancel() {
                    window.location = '{{ route("processes.index") }}';
                },
                importFile() {
                    let formData = new FormData();
                    formData.append('file', this.file);
                    ProcessMaker.apiClient.post( '/process/import',
                        formData,
                        {
                            headers: {
                                'Content-Type': 'multipart/form-data'
                            }
                        }
                        ).then(function(){
                        console.log('SUCCESS!!');
                        })
                        .catch(function(){
                        console.log('FAILURE!!');
                        });
                }
            }
        })
    </script>
@endsection
