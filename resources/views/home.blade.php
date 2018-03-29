@extends('layouts.layout')

@section('content')
  <div class="row">

    <h4>Completed tasks<small class="font-weight-light">  (100)</small></h4>
    <div class="form-group mx-sm-3 mb-2">
      <input class="form-control" type="search" placeholder="Search..." aria-label="Search">
    </div>
  <table class="table table-hover" id="firstTable">
    <thead>
      <tr>
        <th>Name</th>
        <th>profession</th>
        <th>phone</th>
      </tr>
    </thead>
    <tbody>
    <tr v-for="row in rows">
      <td>@{{row.name}}</td>
      <td>@{{row.profession}}</td>
      <td>@{{row.phone}}</td>
    </tr>
    </tbody>
</table>
@endsection

@section('sidebar')
  @include('sidebars.default')
@endsection
@section('js')
  <script>
 var firstTable = new Vue({
   el: '#firstTable',
   data: {
     rows: [
       { id: 1, name: "Chandler Bing", phone: '305-917-1301', profession: 'IT Manager' },
       { id: 2, name: "Ross Geller", phone: '210-684-8953', profession: 'Paleontologist' },
       { id: 3, name: "Rachel Green", phone: '765-338-0312', profession: 'Waitress'},
       { id: 4, name: "Monica Geller", phone: '714-541-3336', profession: 'Head Chef' },
       { id: 5, name: "Joey Tribbiani", phone: '972-297-6037', profession: 'Actor' },
       { id: 6, name: "Phoebe Buffay", phone: '760-318-8376', profession: 'Masseuse' }
     ]
   }
 });
 </script>
@endsection
