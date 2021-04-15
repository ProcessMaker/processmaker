@extends('layouts.layout')

@section('title')
test status
@endsection

@section('sidebar')
@endsection

@section('breadcrumbs')
@endsection

@section('content')
<div id="app" class="p-2" v-cloak>
  <div><b>Broadcaster:</b> @{{ broadcast }}</div>
  <div class="d-flex"><b>Echo:</b><span class="pl-2"><div v-for="e in echo" :key="e">@{{ e }}</div></span></div>
  <div v-if="close"><button class="btn btn-success" v-on:click="closeWindow">Tests completed, close window</button></div>
</div>
@endsection

@section('js')
<script>
  new Vue({
    'el': '#app',
    data() {
      return {
        broadcast: window.Processmaker.broadcasting.broadcaster,
        echo: ['Waiting for message'],
        close: false,
      };
    },
    mounted() {
      window.Echo.private(`test.status`)
        .listen('.TestStatusEvent', (e) => {
          this.echo.push(e.description);
          this.echo.push('Send acknowledgement');
          ProcessMaker.apiClient.get('test_acknowledgement').then(() => {
            this.echo.push("SUCCESS");
            this.close = true;
            Echo.leave(`test.status`);
            setTimeout(() => window.close(), 500);
          });
        });
    },
    methods: {
      closeWindow() {
        window.close();
      },
    },
  })
</script>
@endsection