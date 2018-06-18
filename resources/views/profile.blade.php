@extends('layouts.layout')

@section('content')
<div class="form-wrap container bg-light mt-4 p-5">
  <h3 class="pl-5">Profile</h3>
  <div>
    <div class="custom-file">
      <label for="customFile"><img class="profile-avatar" align="center" src="../img/avatar.png"/></label>
      <img class="profile-avatar" align="center" src="../img/avatar-profile-overlay.png"/>
      <input type="file" class="custom-file-input" id="customFile">
    </div>
  </div>
  <form class="pl-5 pr-5">
    <div class="row form-group">
      <div class="col">
        <label for="inputAddress">Address</label>
        <input type="text" class="form-control" placeholder="First name">
      </div>
      <div class="col">
        <label for="inputAddress">Address</label>
        <input type="text" class="form-control" placeholder="Last name">
      </div>
    </div>
    <div class="row form-group">
      <div class="col">
        <label for="inputAddress">Address</label>
        <input type="text" class="form-control" placeholder="First name">
      </div>
      <div class="col">
        <label for="inputAddress">Address</label>
        <input type="text" class="form-control" placeholder="Last name">
      </div>
    </div>
    <div class="row form-group">
      <div class="col">
        <label for="inputAddress">Address</label>
        <input type="text" class="form-control" placeholder="First name">
      </div>
      <div class="col">
        <label for="inputAddress">Address</label>
        <input type="text" class="form-control" placeholder="Last name">
      </div>
    </div>
    <br>
    <div class="row form-group">
      <div class="col">
       <label for="inputAddress">Address</label>
       <input type="text" class="form-control" id="inputAddress" placeholder="1234  St">
     </div>
   </div>
   <div class="row form-group">
     <div class="col">
      <label for="inputState">State</label>
      <select id="inputState" class="form-control">
        <option selected>Choose...</option>
        <option>...</option>
      </select>
      </div>
      <div class="col">
        <label for="inputState">State</label>
        <select id="inputState" class="form-control">
          <option selected>Choose...</option>
          <option>...</option>
        </select>
      </div>
    </div>
   <div class="row form-group">
     <div class="col">
       <label for="inputAddress">Address</label>
       <input type="text" class="form-control" placeholder="First name">
     </div>
      <div class="col">
        <label for="inputState">State</label>
        <select id="inputState" class="form-control">
          <option selected>Choose...</option>
          <option>...</option>
        </select>
      </div>
    </div>
   <div class="row form-group">
     <div class="col">
       <label for="inputAddress">Address</label>
       <input type="text" class="form-control" placeholder="First name">
     </div>
     <div class="col">
       <label for="inputAddress">Address</label>
       <input type="text" class="form-control" placeholder="First name">
     </div>
    </div>
   <div class="row form-group">
     <div class="col">
       <label for="inputAddress">Address</label>
       <input type="text" class="form-control" placeholder="First name">
     </div>
     <div class="col">
       <label for="inputAddress">Address</label>
       <input type="text" class="form-control" placeholder="First name">
     </div>
    </div>
    <div class="row form-group float-right mt-3">
      <div class="col">
        <button type="button" class="btn btn-outline-secondary">Cancel</button>
        <button type="button" class="btn btn-secondary text-light">Save</button>
      </div>
    </div>
  </form>
</div>
@endsection

@section('css')
<style lang="scss" scoped>
  .form-wrap{
    max-width: 620px;
  }
  .profile-avatar{
    width: 82px;
    height: 82px;
    margin-left: 220px;
  }
  h3{
    font-size: 24px;
  }
</style>
@endsection


@section('js')

@endsection
