@extends('layouts.process-map')

@section('title')
  {{ __('Process Map') }}
@endsection

@section('content')
  <div id="modeler-app"></div>
@endsection

@section('css')
  <style>
    div.main {
      position: relative;
    }

    #modeler-app {
      position: relative;
      width: 100%;
      max-width: 100%;
      height: 100%;
      max-height: 100%;
    }
  </style>
@endsection

@section('js')
  <script>
    const breadcrumbData = [];
    window.ProcessMaker.modeler = {
      xml: @json($bpmn),
      enableProcessMapping: false,
    }
  </script>


  <script>
    app = new Vue({
      el: '#modeler-app',
      data() {
        return {
          iframeLoaded: false,
          modelerStarted: false,
          loadModel: null
        }
      },
      mounted() {
        window.ProcessMaker.EventBus.$on('modeler-start', async ({ loadXML }) => {
          this.loadModel = loadXML;
          this.modelerStarted = true;
        });
        window.ProcessMaker.EventBus.$on('iframe-loaded', () => {
          this.iframeLoaded = true;
        });
      },
      watch: {
        modelerStarted(value) {
          if (value && this.iframeLoaded) {
            this.load();
          }
        },
        iframeLoaded(value) {
          if (value && this.modelerStarted) {
            this.load();
          }
        }
      },
      methods: {
        async load() {
          await this.loadModel(window.ProcessMaker.modeler.xml);
          window.ProcessMaker.EventBus.$emit('parsed');
        }
      }
    });
  </script>


  @foreach ($manager->getScripts() as $script)
    @if (str_contains($script, 'package-ab-testing'))
      <script type="module" src="{{ $script }}"></script>
    @else
      <script src="{{ $script }}"></script>
    @endif
  @endforeach
  <script src="{{ mix('js/processes/modeler/process-map.js') }}"></script>
@endsection
