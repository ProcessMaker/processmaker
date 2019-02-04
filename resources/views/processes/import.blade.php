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
                    <h5>Import Process</h5>
                </div>
                <div class="card-body">
                    <h5 class="card-title">You are about to import a Process</h5>
                    <p class="card-text">You will need to fix hecka stuff</p> 
                    <input type="file" ref="file" class="d-none" @change="handleFile">
                    <button @click="$refs.file.click()" class="btn btn-secondary ml-2">
                        <i class="fas fa-upload"></i>
                        Browse
                    </button>
                </div>
                <div class="card-footer bg-light" align="right">
                    <button type="button" class="btn btn-outline-secondary" @click="onCancel">Cancel</button>
    			    <button type="button" class="btn btn-secondary ml-2" >Import</button>
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
                
            },
            methods: {
                handleFile(e) {
                    console.log(e.target.files[0])
                     this.$emit('input', e.target.files[0])
                },
                onCancel() {
                    window.location = '{{ route("processes.index") }}';
                },
            }
        })
    </script>
@endsection