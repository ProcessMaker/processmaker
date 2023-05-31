@extends('layouts.layout')

@section('title')
    {{__('Export Translation')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Designer') => route('processes.index'),
        __('Processes') => route('processes.index'),
        __('Export' . ' ' . $process->name . ' - ' . $language['humanLanguage']) => null,
    ]])
@endsection
@section('content')

    <div class="container" id="exportProcessTranslation">
      <div class="row">
        <div class="col">
          <div class="card text-center">
            <div class="card-header bg-light" align="left">
              <h5>{{ __("Export Process Translation") }}</h5>
              <h6 class="text-muted">Download the {{ $language['humanLanguage'] }} translations for each screen asociated to the process.</h6>
            </div>
            <div class="card-body" align="left">
              <h5 class="card-title export-type">{{ __("You are about to export") }} <b>{{ $language['humanLanguage'] }}</b> {{ __("Translations for the process") }} 
                <span class="font-weight-bold">{{ $process->name . "."}}</span>
              </h5>
            </div>
            <div class="card-footer bg-light" align="right">
              <button type="button" class="btn btn-outline-secondary" @click="onCancel">
                {{ __("Cancel") }}
              </button>
              <button type="button" class="btn btn-primary ml-2" @click="onExport">
                {{ __("Export") }}
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
        el: '#exportProcessTranslation',
        data: {
          processId: @json($process->id),
          language: @json($language)
        },
        methods: {
          onCancel() {
            window.location = '{{ route("processes.index") }}';
          },
          onExport() {
            ProcessMaker.apiClient({
              method: 'POST',
              url: 'processes/' + this.processId + '/export/translation/' + this.language.language,
              responseType: 'blob',
              data: {}
            }).then(response => {
              console.log(response);
              // const exportInfo = JSON.parse(response.headers['fileName']);
              const url = window.URL.createObjectURL(new Blob([response]));
              const link = document.createElement("a");
              link.href = url;

              // link.setAttribute("download", `${exportInfo.name.replace(' ', '_')}.json`);
              link.setAttribute("download", `translations.json`);
              document.body.appendChild(link);
              link.click();
              // ProcessMaker.alert(`The translation ${exportInfo.name} was exported`, 'success');
              ProcessMaker.alert(`The translation was exported`, 'success');
            });
          },
        }
      })
    </script>
@endsection
