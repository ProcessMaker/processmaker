@extends('layouts.layout')
@section('title')
Login
@endsection
@section('content')
<div class="d-flex flex-column" style="min-height: 100vh">
<div class="flex-fill">
  <div align="center" class="p-5">
    @component('components.logo')
    @endcomponent
  </div>

  <div class="container" id="main">
    <input id="message" v-model="message" class="form-control" placeholder="Write here...">
    <input id="answer" v-model="answer" class="form-control" placeholder="">
    <button @click="sendMessage" class="btn btn-primary">Send</button>
  </div>
</div>


@endsection

@section('js')
    <script>
      new Vue({
        el: '#main',
        data() {
          return {
            message: "",
            answer: "",
          };
        },
        methods: {
          sendMessage() {
            ProcessMaker.apiClient.post(`http://192.168.0.7:8010/pm/test_call_function`, {
                userPrompt: this.message,
                sessionId: "ps-test"
            })
            .then(response => {
              this.answer = response.data.message
              console.log(response);
            });
          }
        }
      });

    </script>
@endsection

@section('css')

@endsection
