@extends('layouts.layout')

@section('content')

<h4>User Profile</h4>
<div class="p-5 container bg-light">
  <img class="avatar align-items-center" src="img/avatar.png">
  <form class="p-5">
    <div class="row">
      <div class="col">
      <label class="pt-2 text-muted" class="pt-2">Example select</label>
        <input type="text" class="form-control" placeholder="First name">
      <label class="pt-2 text-muted" class="pt-2">Example select</label>
        <input type="text" class="form-control" placeholder="First name">
      <label class="pt-2 text-muted">Example select</label>
        <input type="text" class="form-control" placeholder="First name">
      <label class="pt-2 text-muted">Example select</label>
        <input type="text" class="form-control" placeholder="First name">
      <label class="pt-2 text-muted">Example select</label>
        <input type="text" class="form-control" placeholder="First name">
      <label class="pt-2 text-muted">Example select</label>
        <input type="text" class="form-control" placeholder="First name">
      </div>
    <div class="col form-group">
      <label class="pt-2 text-muted">Example select</label>
        <select class="form-control" id="exampleFormControlSelect1">
          <option>1</option>
          <option>2</option>
          <option>3</option>
          <option>4</option>
          <option>5</option>
        </select>
      <label class="pt-2 text-muted">Example select</label>
        <select class="form-control" id="exampleFormControlSelect1">
          <option>1</option>
          <option>2</option>
          <option>3</option>
          <option>4</option>
          <option>5</option>
        </select>
      <label class="pt-2 text-muted">Example select</label>
        <select class="form-control" id="exampleFormControlSelect1">
          <option>1</option>
          <option>2</option>
          <option>3</option>
          <option>4</option>
          <option>5</option>
        </select>
      <label class="pt-2 text-muted">Example select</label>
        <select class="form-control" id="exampleFormControlSelect1">
          <option>1</option>
          <option>2</option>
          <option>3</option>
          <option>4</option>
          <option>5</option>
        </select>
      <label class="pt-2 text-muted">Example select</label>
        <select class="form-control" id="exampleFormControlSelect1">
          <option>1</option>
          <option>2</option>
          <option>3</option>
          <option>4</option>
          <option>5</option>
        </select>
      <label class="pt-2 text-muted">Example select</label>
        <select class="form-control" id="exampleFormControlSelect1">
          <option>1</option>
          <option>2</option>
          <option>3</option>
          <option>4</option>
          <option>5</option>
        </select>
      </div>
    </div>
    <button type="submit" class="btn btn-secondary m-2 float-right">Save</button>
    <button type="submit" class="btn btn-outline-secondary m-2 float-right"> Cancel</button>
  </form>
</div>
@endsection

@section('sidebar')
  @include('sidebars.default')
@endsection

@section('css')
  <style>
    .avatar{
      height:104px;
      width: 104px;
    }
  </style>
@endsection

@section('js')
  <script>

</script>
@endsection
