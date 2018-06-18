@extends('layouts.layout')

@section('content')
<div class="form-wrap container bg-light mt-4 p-5">
  <h3>Profile</h3>
  <form>
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
    <div class="row form-group">
      <div class="col">
       <label for="inputAddress">Address</label>
       <input type="text" class="form-control" id="inputAddress" placeholder="1234 Main St">
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
    <div class="form-group float-right">
      <div class="col ">
        <button type="button" class="btn btn-outline-secondary">Secondary</button>
        <button type="button" class="btn btn-secondary">Secondary</button>
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
</style>
@endsection


@section('js')

@endsection
