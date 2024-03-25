@extends('layouts.layout')

@section('title')
    {{__('Export Screen Template')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route('processes.index'),
        __('Screens') => route('screens.index'),
        __('Export'.' '.$screen->title) => null,
    ]])
@endsection
@section('content')
    <div class="container" id="exportScreen">
        <div class="row">
            <div class="col">
                <div class="card text-center">
                    <div class="card-header bg-light" align="left">
                        <h5>{{__('Export Screen Template')}}</h5>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">{{__('You are about to export a Screen Template.')}}</h5>
                        <h6>"{{$screen->name}}"</h6>
                        <p class="card-text">{{__('All the configurations of the screen template will be exported.')}}</p>
                    </div>
                    <div class="card-footer bg-light" align="right">
                        <button type="button" class="btn btn-outline-secondary" @click="onCancel">
                            {{__('Cancel')}}
                        </button>
                        <button type="button" class="btn btn-secondary ml-2" @click="onExport">
                            {{__('Download')}}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
      new Vue({
        el: '#exportScreen',
        data: {
          screenId: @json($screen->id)
        },
        methods: {
          onCancel() {
            window.location = '{{ route("screens.index") }}';
          },
          onExport() {
            console.log(this.screenId);
            ProcessMaker.apiClient.get('export/manifest/screen-template/' + this.screenId )
              .then(response => {
                window.location = response.data.url;
                ProcessMaker.alert(this.$t('The screen was exported.'), 'success');
              })
              .catch(error => {
                console.log('error', error);
                ProcessMaker.alert(error.response.data.message, 'danger');
              });
          }
        }
      })
    </script>
@endsection
