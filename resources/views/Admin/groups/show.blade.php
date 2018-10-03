@extends('layouts.layout', ['title' => 'Group Management'])

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')

<div class="container mt-4">
  <div class="card card-body">
    <div class="container">
      <div class="row">
        <div class="col">
          <div class="d-flex"><h3>Administrator</h3><i class="fas fa-circle text-success mt-2 ml-1 small"></i></div>
          <h5>Group of users with a full set of permissions</h5>
          <div class="mt-4">Created:<span class="font-weight-bold"> 01/01/0101 01:00 </span></div>
          <div class="mt-2 mb-4">Updated: <span class="font-weight-bold"> 02/01/0101 02:00 </span></div>
        </div>
        <div class="col text-right">
            <button class="btn btn-outline-secondary"> <i class="fas fa-lock"></i> permissions</button>
            <button class="btn btn-secondary"> <i class="fas fa-edit"></i> edit</button>
            <button class="btn btn-secondary"> <i class="fas fa-copy"></i> copy</button>
            <button class="btn btn-secondary"> <i class="fas fa-trash-alt"></i> delete</button>
        </div>
      </div>
      <br>
      <div class="row">
        <div class="col">
          <h3>Members</h3>
        </div>
        <div class="col-3">
          <input type="text" class="form-control" placeholder="&#xf0e0; Search">
        </div>
        <button type="submit" class="btn btn-secondary mr-3"> <i class="fas fa-plus"></i> User</button>
      </div>
        <table class="table table-hover mt-4 vuetable">
        <thead>
          <tr>
            <th>Full Name<i class="sort-icon fas fa-sort ml-2"></i></th>
            <th>Email<i class="sort-icon fas fa-sort ml-2"></i></th>
            <th>Status<i class="sort-icon fas fa-sort ml-2"></i></th>
            <th class="pr-5"></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td scope="row"><img src="{{ asset('img/avatar_placeholder_small.png') }}"/> {{$group->name}} </td>
            <td>email@email.com</td>
            <td><i class="fas fa-circle text-success small"></i> Active</td>
            <td class="actions popout vuetable-slot"><i class="fas fa-trash-alt"></i></td>
          </tr>
        </tbody>
      </table>
      <div class="text-secondary">1 - 4 of 4 Members</div>
  </div>
</div>

@endsection

@section('js')
<script src="{{mix('js/management/groups/index.js')}}"></script>
@endsection

@section('css')
<style lang="scss" scoped>

.fa-circle {
  fontsize: 10px;
}

tbody tr td img {
  height: 20px; 
  border-radius: 50%;
}

</style>
@endsection