@extends('layouts.layout')

@section('content')
  <div class="table-responsive">
  <div class="form-inline justify-content-between">
    <strong class="table-header">Completed tasks<small class="font-weight-light">  (100)</small></strong>
    <form>
      <input class="form-control" type="search" placeholder="Search..." aria-label="Search">
    </form>
  </div>
  <table class="table  table-hover">
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
      <td>1</td>
      <td>Mark</td>
      <td>Otto</td>
      <td>@mdo</td>
      <td>Mark</td>
      <td>Otto</td>
      <td>@mdo</td>
      <td>icon</td>
    </tr>
    <tr>
      <td>2</td>
      <td>Jacob</td>
      <td>Thornton</td>
      <td>@fat</td>
      <td>Jacob</td>
      <td>Thornton</td>
      <td>@fat</td>
      <td>icon</td>
    </tr>
    <tr>
      <td>3</td>
      <td>Larry</td>
      <td>the Bird</td>
      <td>@twitter</td>
      <td>Larry</td>
      <td>the Bird</td>
      <td>@twitter</td>
      <td>icon</td>
    </tr>
    <tr>
      <td>4</td>
      <td>Mark</td>
      <td>Otto</td>
      <td>@mdo</td>
      <td>Mark</td>
      <td>Otto</td>
      <td>@mdo</td>
      <td>icon</td>
    </tr>
    <tr>
      <td>5</td>
      <td>Jacob</td>
      <td>Thornton</td>
      <td>@fat</td>
      <td>Jacob</td>
      <td>Thornton</td>
      <td>@fat</td>
      <td>icon</td>
    </tr>
    <tr>
      <td>6</td>
      <td>Larry</td>
      <td>the Bird</td>
      <td>@twitter</td>
      <td>Larry</td>
      <td>the Bird</td>
      <td>@twitter</td>
      <td>icon</td>
    </tr>
    <tr>
      <td>7</td>
      <td>Mark</td>
      <td>Otto</td>
      <td>@mdo</td>
      <td>Mark</td>
      <td>Otto</td>
      <td>@mdo</td>
      <td>icon</td>
    </tr>
    <tr>
      <td>8</td>
      <td>Jacob</td>
      <td>Thornton</td>
      <td>@fat</td>
      <td>Jacob</td>
      <td>Thornton</td>
      <td>@fat</td>
      <td>icon</td>
    </tr>
    <tr>
      <td>9</td>
      <td>Larry</td>
      <td>the Bird</td>
      <td>@twitter</td>
      <td>Larry</td>
      <td>the Bird</td>
      <td>@twitter</td>
      <td>icon</td>
    </tr>
  </tbody>
</table>
</div>
@endsection

@section('sidebar')
  @include('sidebars.default')
@endsection

@section('js')

@endsection
