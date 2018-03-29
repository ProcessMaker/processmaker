@extends('layouts.layout')

@section('content')
  <div class="table-responsive">
  <div class="form-inline justify-content-between">
    <strong class="table-header">Completed tasks<small class="font-weight-light">  (100)</small></strong>
    <form>
      <input class="form-control" type="search" placeholder="Search..." aria-label="Search">
    </form>
  </div>
  <table class="table table-sm table-hover" id="fourthTable">
  <thead>
    <tr>
      <th class="text-dark" scope="col" v-for="col in columns" v-on:click="sortTable(col)"> @{{col}}   <i class="fa fa-sort-desc" aria-hidden="true"></i></i></th>
    </tr>
  </thead>
  <tbody>
    <tr v-for="row in rows">
      <td v-for="col in columns">@{{row[col]}}</td>
    </tr>
  </tbody>
</table>
</div>
@endsection

@section('sidebar')
  @include('sidebars.default')
@endsection

@section('js')
  <script>
  var fourthTable = new Vue({
    el: '#fourthTable',
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
@endsection
