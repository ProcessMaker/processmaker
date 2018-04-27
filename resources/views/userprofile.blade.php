@extends('layouts.layout')

@section('content')

<h4>User Profile</h4>
<div class="p-5 container bg-light">
  <div class="h-25">
    <img class="avatar rounded-circle" src="img/avatar.png">
    <a class="centered text-light text-center" href="#"><i class="fas fa-camera fa-lg"></i><br><span class="avatarlink">Change Profile Photo</span></a>
  </div>
  <form class="p-5">
    <div class="row">
      <div class="col">
      <label class="pt-2 text-muted" class="pt-2">First Name</label>
        <input type="text" class="form-control" >
      <label class="pt-2 text-muted" class="pt-2">Last Name</label>
        <input type="text" class="form-control" >
      <label class="pt-2 text-muted">Username</label>
        <input type="text" class="form-control" >
      <label class="pt-2 text-muted">Email</label>
        <input type="text" class="form-control" >
      <label class="pt-2 text-muted">Expiraion Date</label>
        <input type="text" class="form-control" >
      <label class="pt-2 text-muted">Password</label>
        <input type="text" class="form-control" >
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
    <button type="submit" class="btn btn-secondary text-light m-2 float-right">Save</button>
    <a href="{{url('home')}}" class="btn btn-outline-secondary m-2 float-right"> Cancel</a>
  </form>
</div>
@endsection

@section('sidebar')
  @include('sidebars.default')
@endsection

@section('css')
  <style>
    label{
       font-size: 14px;
    }
    .avatarlink{
      font-size: 12px;
    }
    .avatar{
    height:104px;
    width: 104px;
    display: block;
    margin: auto;
    filter: brightness(50%);
    }
    .centered {
    font-size: 14px;
    position: absolute;
    top: 20%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 88px;
    }
  </style>
@endsection

@section('js')
  <script>

</script>
@endsection
