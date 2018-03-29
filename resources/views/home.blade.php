@extends('layouts.layout')

@section('content')
  <div class="table-responsive">
  <div class="form-inline justify-content-between m-3">
    <h4>Completed tasks<small class="font-weight-light">  (100)</small></h4>
    <form>
      <input class="form-control" type="search" placeholder="Search..." aria-label="Search">
    </form>
  </div>
  <table class="table table-sm table-hover">
  <thead>
    <tr>
      <th class="text-dark" scope="col">CASE   <i class="fa fa-sort-desc" aria-hidden="true"></i></i></th>
      <th class="text-muted" scope="col">PROCESS   <i class="fa fa-sort" aria-hidden="true"></i></th>
      <th class="text-muted" scope="col">TASK   <i class="fa fa-sort" aria-hidden="true"></i></th>
      <th class="text-muted" scope="col">SENT BY   <i class="fa fa-sort" aria-hidden="true"></i></th>
      <th class="text-muted" scope="col">DUE   <i class="fa fa-sort" aria-hidden="true"></i></th>
      <th class="text-muted" scope="col">MODIFIED   <i class="fa fa-sort" aria-hidden="true"></i></th>
      <th class="text-muted" scope="col">PRIORITY   <i class="fa fa-sort" aria-hidden="true"></i></th>
      <th class="text-muted" scope="col">INFO   <i class="fa fa-sort" aria-hidden="true"></i></th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <th scope="row">1</th>
      <td>Mark</td>
      <td>Otto</td>
      <td>@mdo</td>
      <td>Mark</td>
      <td>Otto</td>
      <td>@mdo</td>
      <td><i class="fa fa-table btn" aria-hidden="true"></i></td>
    </tr>
    <tr>
      <th scope="row">2</th>
      <td>Jacob</td>
      <td>Thornton</td>
      <td>@fat</td>
      <td>Jacob</td>
      <td>Thornton</td>
      <td>@fat</td>
      <td><i class="fa fa-table btn" aria-hidden="true"></i></td>
    </tr>
    <tr>
      <th scope="row">3</th>
      <td>Larry</td>
      <td>the Bird</td>
      <td>@twitter</td>
      <td>Larry</td>
      <td>the Bird</td>
      <td>@twitter</td>
      <td><i class="fa fa-table btn" aria-hidden="true"></i></td>
    </tr>
    <tr>
      <th scope="row">4</th>
      <td>Mark</td>
      <td>Otto</td>
      <td>@mdo</td>
      <td>Mark</td>
      <td>Otto</td>
      <td>@mdo</td>
      <td><i class="fa fa-table btn" aria-hidden="true"></i></td>
    </tr>
    <tr>
      <th scope="row">5</th>
      <td>Jacob</td>
      <td>Thornton</td>
      <td>@fat</td>
      <td>Jacob</td>
      <td>Thornton</td>
      <td>@fat</td>
      <td><i class="fa fa-table btn" aria-hidden="true"></i></td>
    </tr>
    <tr>
      <th scope="row">6</th>
      <td>Larry</td>
      <td>the Bird</td>
      <td>@twitter</td>
      <td>Larry</td>
      <td>the Bird</td>
      <td>@twitter</td>
      <td><i class="fa fa-table btn" aria-hidden="true"></i></td>
    </tr>
    <tr>
      <th scope="row">7</th>
      <td>Mark</td>
      <td>Otto</td>
      <td>@mdo</td>
      <td>Mark</td>
      <td>Otto</td>
      <td>@mdo</td>
      <td><i class="fa fa-table btn" aria-hidden="true"></i></td>
    </tr>
    <tr>
      <th scope="row">8</th>
      <td>Jacob</td>
      <td>Thornton</td>
      <td>@fat</td>
      <td>Jacob</td>
      <td>Thornton</td>
      <td>@fat</td>
      <td><i class="fa fa-table btn" aria-hidden="true"></i></td>
    </tr>
    <tr>
      <th scope="row">9</th>
      <td>Larry</td>
      <td>the Bird</td>
      <td>@twitter</td>
      <td>Larry</td>
      <td>the Bird</td>
      <td>@twitter</td>
      <td><i class="fa fa-table btn" aria-hidden="true"></i></td>
    </tr>
  </tbody>
</table>
</div>
@endsection

@section('sidebar')
  @include('sidebars.default')
@endsection
