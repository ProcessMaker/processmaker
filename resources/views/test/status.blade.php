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
  <div><b>Echo:</b> @{{ echo }}</div>
</div>
@endsection

@section('js')
<script>
  new Vue({
    'el': '#app',
    data() {
      return {
        broadcast: window.Processmaker.broadcasting.broadcaster,
        echo: 'Waiting echo event',
      };
    },
    mounted() {
      window.Echo.private(`test.status`)
        .listen('.TestStatusEvent', (e) => {
            this.echo = e.description;
        });
    },
  })
</script>
@endsection