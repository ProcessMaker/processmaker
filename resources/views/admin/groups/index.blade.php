@extends('layouts.layout', ['title' => 'Group Management'])

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
<div class="container mt-4">
    <div class="row mt-5">
        <div class="col-1">
        <h3>{{__('Groups')}}</h3>
        </div>
        <div class="col-3">
        <input type="text" class="form-control" placeholder="&#xf0e0; Search">
        </div>
        <div class="col"></div>
        <button type="submit" class="btn btn-secondary mr-3"> <i class="fas fa-plus"></i> Group</button>
    </div>
    <table class="table table-hover mt-4 vuetable">
      <thead>
        <tr>
          <th scope="col">Name<i class="sort-icon fas fa-sort ml-2"></i></th>
          <th scope="col">Description<i class="sort-icon fas fa-sort ml-2"></i></th>
          <th scope="col">Status<i class="sort-icon fas fa-sort ml-2"></i></th>
          <th scope="col">Members<i class="sort-icon fas fa-sort ml-2"></i></th>
          <th scope="col"></th>
        </tr>
      </thead>
      <tbody>
      @foreach ($groups as $group)
        <tr>
          <td scope="row">{{$group->name}}</td>
          <td>This is a description</td>
          <td><i class="fas fa-circle text-success small"></i> Active</td>
          <td>45</td>
          <td class="actions popout vuetable-slot"><i class="fas fa-ellipsis-h"></i></td>
        </tr>
        @endforeach
      </tbody>
    </table>
    <div class="text-secondary">1 - 4 of 4 Groups</div>
</div>
@endsection

@section('js')
    <script src="{{mix('js/admin/groups/index.js')}}"></script>
@endsection
