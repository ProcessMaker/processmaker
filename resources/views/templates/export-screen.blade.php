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
                    <div class="card-header bg-light text-left">
                        <h5>{{__('Export Screen Template')}}</h5>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">{{__('You are about to export a Screen Template.')}}</h5>
                        <h6>"{{$screen->name}}"</h6>
                        <p class="card-text">
                            {{__('All the configurations of the screen template will be exported.')}}
                        </p>
                    </div>
                    <div class="card-footer bg-light text-right">
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
            ProcessMaker.apiClient.post(`export/screen-template/download/${this.screenId}`)
              .then(response => {
                const jsonData = JSON.stringify(response.data);
                const blob = new Blob([jsonData], {type: 'application/json'});
                const url = URL.createObjectURL(blob);

                // Create a link and trigger the download
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', response.data.name);
                document.body.appendChild(link);
                link.click();
    
                // Clean up by removing the link and revoking the Blob URL
                document.body.removeChild(link);
                URL.revokeObjectURL(url);

                ProcessMaker.alert(this.$t('The Screen Template was exported.'), 'success');
              })
              .catch(error => {
                ProcessMaker.alert(error.response.data.message, 'danger');
              });
          }
        }
      })
    </script>
@endsection
