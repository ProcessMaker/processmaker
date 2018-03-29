@extends('layouts.layout')

@section('content')
<<<<<<< HEAD
  <div class="table-responsive">
  <div class="form-inline justify-content-between">
    <strong class="table-header">Completed tasks<small class="font-weight-light">  (100)</small></strong>
    <form>
      <input class="form-control" type="search" placeholder="Search..." aria-label="Search">
    </form>
  </div>
  <table class="table table-sm table-hover" id="dataTable">
  <thead>
    <tr>
      <th class="text-dark" scope="col" v-for="col in columns" v-on:click="sortTable(col)"> @{{col}}   <i class="fa fa-sort" aria-hidden="true"></i></th>
    </tr>
  </thead>
  <tbody>
    <tr v-for="row in rows">
      <td v-for="col in columns">@{{row[col]}}</td>
    </tr>
  </tbody>
=======
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
>>>>>>> b5cdaebafa965b43a4780ebb12e4a9025445062a
</table>
@endsection

@section('sidebar')
  @include('sidebars.default')
@endsection
@section('js')
  <script>
<<<<<<< HEAD
  var dataTable = new Vue({
    el: '#dataTable',
    data: {
      ascending: false,
      sortColumn: '',
      rows: [
        { id: 1, name: "Chandler Bing", phone: '305-917-1301', profession: 'IT Manager' },
        { id: 2, name: "Ross Geller", phone: '210-684-8953', profession: 'Paleontologist' },
        { id: 3, name: "Rachel Green", phone: '765-338-0312', profession: 'Waitress'},
        { id: 4, name: "Monica Geller", phone: '714-541-3336', profession: 'Head Chef' },
        { id: 5, name: "Joey Tribbiani", phone: '972-297-6037', profession: 'Actor' },
        { id: 6, name: "Phoebe Buffay", phone: '760-318-8376', profession: 'Masseuse' }
      ]
    },
    methods: {
      "sortTable": function sortTable(col) {
        if (this.sortColumn === col) {
          this.ascending = !this.ascending;
        } else {
          this.ascending = true;
          this.sortColumn = col;
        }

        var ascending = this.ascending;

        this.rows.sort(function(a, b) {
          if (a[col] > b[col]) {
            return ascending ? 1 : -1
          } else if (a[col] < b[col]) {
            return ascending ? -1 : 1
          }
          return 0;
        })
      }
    },
    computed: {
      "columns": function columns() {
        if (this.rows.length == 0) {
          return [];
        }
        return Object.keys(this.rows[0])
      }
    }
  });
</script>
=======
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
>>>>>>> b5cdaebafa965b43a4780ebb12e4a9025445062a
@endsection
