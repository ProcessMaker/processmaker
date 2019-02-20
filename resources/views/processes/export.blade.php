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
<div class="container" id="exportProcess">
    <div class="row">
        <div class="col">
            <div class="card text-center">
                <div class="card-header bg-light" align="left">
                    <h5>{{__('Export Process')}}</h5>
                </div>
                <div class="card-body">
                    <h5 class="card-title">{{__('You are about to export a Process.')}}</h5>
                    <p class="card-text">{{__('User assignments and sensitive environment variables will not be exported.')}}</p> 
                </div>
                <div class="card-footer bg-light" align="right">
                    <button type="button" class="btn btn-outline-secondary" @click="onCancel">{{__('Cancel')}}</button>
    			    <button type="button" class="btn btn-secondary ml-2" @click="onExport">{{__('Download')}}</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
    <script>
        new Vue({
            el: '#exportProcess',
            data: {
                processId: @json($process->id)
            },
            methods: {
                onCancel() {
                    window.location = '{{ route("processes.index") }}';
                },
                onExport() {
                    ProcessMaker.apiClient.post('processes/' + this.processId + '/export')
                        .then(response => {
                            window.location = response.data.url;
                            ProcessMaker.alert('{{__('Process Exported')}}', 'success');
                        })
                        .catch(error => {
                            ProcessMaker.alert(error.response.data.error, 'danger');
                        });
                }
            }
        })
    </script>
@endsection