@extends('layouts.layout', ['title' => __('Environmental Variables')])

@section('sidebar')
  @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
    <div class="container mt-4">
        <div class="row mt-5">
          <div class="col-4" style="margin-top: 3px;">
            <h3>Environmental Variables</h3>
          </div>
          <div class="col-3" style="margin-left: -119px; margin-right: 74px;">
            <input type="text" class="form-control" placeholder="&#xf0e0; Search">
          </div>
          <div class="col-4"></div>
          <div class="col">
            <button class="btn btn-secondary"><i class="fas fa-plus"></i> process</button>
          </div>
      </div>
      <table class="table table-hover mt-4 vuetable"  id="app">
      <thead>
        <tr>
          <th scope="col">Process<i class="sort-icon fas fa-sort ml-2"></i></th>
          <th scope="col">Category<i class="sort-icon fas fa-sort ml-2"></i></th>
          <th scope="col">Status<i class="sort-icon fas fa-sort ml-2"></i></th>
          <th scope="col">Modified By <i class="sort-icon fas fa-sort ml-2"></i></th>
          <th scope="col">Created <i class="sort-icon fas fa-sort ml-2"></i></th>
          <th scope="col">Modified <i class="sort-icon fas fa-sort ml-2"></i></th>
          <th scope="col"></th>
        </tr>
      </thead>
      <tbody>
        <tr @mouseover="showButtons" @mouseout="seen=false">
          <td scope="row">This is a thing</td>
          <td>HR</td>
          <td class="text-uppercase"><i class="fas fa-circle text-success small"></i> active</td>
          <td><img src="../avatar-placeholder.gif" style="height: 20px; border-radius: 50%;"/>  Name Name</td>
          <td>01/01/1111 11:11</td>
          <td>02/02/2222 22:22</td>
          <td class="vuetable-slot">
            <div class="actions" v-if="seen">
              <div class="popout">
                <i class="fas fa-edit btn"></i><i class="fas fa-stop-circle btn"></i><i class="fas fa-eye btn"></i><i class="fas fa-trash-alt btn"></i>
              </div>
            </div>
          </td>
        </tr>
        <tr>
          <td scope="row">This is another thing description</td>
          <td>Sales</td>
          <td class="text-uppercase"><i class="fas fa-circle text-success small"></i> active</td>
          <td><img src="../avatar-placeholder.gif" style="height: 20px; border-radius: 50%;"/>  Name Name</td>
          <td>01/01/1111 11:11</td>
          <td>02/02/2222 22:22</td>
          <td class="actions popout vuetable-slot"></td>
        </tr>
        <tr>
          <td scope="row">This is another thing description</td>
          <td>Finances</td>
          <td class="text-uppercase"><i class="fas fa-circle text-success small"></i> active</td>
          <td><img src="../avatar-placeholder.gif" style="height: 20px; border-radius: 50%;"/>  Name Name</td>
          <td>01/01/1111 11:11</td>
          <td>02/02/2222 22:22</td>
          <td class="actions popout vuetable-slot"></td>
        </tr>
      </tbody>
    </table>
    <div class="text-secondary">1 - 4 of 4 Proccesses</div>
    </div>
@endsection

@section('js')
    <script>
      new Vue({
        el: '#app',
        data: {
          seen: false
        },
        methods: {
          showButtons(){
            console.log("hey")
            this.seen = true
          }
        }
      })
    </script>
{{-- <script src="{{mix('js/management/environment-variables/index.js')}}"></script> --}}
@endsection
@section('css')
    <style>
    .popout {
      display: block !important;
      white-space: nowrap;
    }
    </style>
@endsection
