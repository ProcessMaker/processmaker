@extends('layouts.layout')

@section('content')
    <div class="container mt-4">
      <h3>Preferences</h3>
      <div class="card card-body">
        <div class="d-flex mb-2">
          <h3 class="mt-1">Localization</h3>
          <button class="btn btn-secondary ml-2 mt-1" style="height:22px; padding-bottom: 21px;"><i class="fas fa-undo-alt"></i>  Reset</button>
        </div>
        <div class="row">
          <div class="form-group col">
            <label for="exampleFormControlSelect1">Time Zone</label>
            <select class="form-control" id="exampleFormControlSelect1">
              <option>1</option>
              <option>2</option>
              <option>3</option>
              <option>4</option>
              <option>5</option>
            </select>
            <small id="emailHelp" class="form-text text-muted">Default Time Zone</small>
          </div>
          <div class="form-group col">
            <label for="exampleFormControlSelect1">Full Name Format</label>
            <select class="form-control" id="exampleFormControlSelect1">
              <option>1</option>
              <option>2</option>
              <option>3</option>
              <option>4</option>
              <option>5</option>
            </select>
            <small id="emailHelp" class="form-text text-muted">Format to display user's full name across all applications</small>
          </div>
        </div>
        <div class="row">
          <div class="form-group col">
            <label for="exampleFormControlSelect1">Global Date Format</label>
            <select class="form-control" id="exampleFormControlSelect1">
              <option>1</option>
              <option>2</option>
              <option>3</option>
              <option>4</option>
              <option>5</option>
            </select>
            <small id="emailHelp" class="form-text text-muted">Default Date Format. Dates across all applications will be displayed using this format</small>
          </div>
          <div class="form-group col">
            <label for="exampleFormControlSelect1">Default Language</label>
            <select class="form-control" id="exampleFormControlSelect1">
              <option>1</option>
              <option>2</option>
              <option>3</option>
              <option>4</option>
              <option>5</option>
            </select>
            <small id="emailHelp" class="form-text text-muted">Default Language to be used across all applications</small>
          </div>
        </div>
        <div class="d-flex mt-5 mb-2">
          <h3 class="mt-1">Email Notifications</h3>
          <button class="btn btn-secondary ml-2 mt-1" style="height:22px; padding-bottom: 21px;"><i class="fas fa-check"></i>  Test</button>
        </div>
        <div class="row">
          <div class="form-group col">
            <label for="exampleInputEmail1">Host Name</label>
            <input type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="Enter email">
            <small id="emailHelp" class="form-text text-muted">Address to the SMTP server used for email notifications</small>
          </div>
          <div class="form-group col">
            <label for="exampleInputEmail1">Username</label>
            <input type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="Enter email">
            <small id="emailHelp" class="form-text text-muted">Enter the account to be authenticated against the SMTP server</small>
          </div>
        </div>
        <div class="row">
          <div class="form-group col">
            <label for="exampleInputEmail1">Server Port</label>
            <input type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="Enter email">
            <small id="emailHelp" class="form-text text-muted">SMTP service port. Default port 25 will be used if you leave blank</small>
          </div>
          <div class="form-group col">
            <label for="exampleInputEmail1">Password</label>
            <input type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="Enter email">
            <small id="emailHelp" class="form-text text-muted">Password used to authenticate the user</small>
          </div>
        </div>
        <div class="row">
          <div class="form-group form-check col">
            <input type="checkbox" class="form-check-input ml-0" id="exampleCheck1">
            <label class="form-check-label ml-3" for="exampleCheck1">SSL/TLS</label>
            <small id="emailHelp" class="form-text text-muted">Enable if SSL/TLS is required by this server</small>
          </div>
          <div class="form-group col">
            <label for="exampleFormControlSelect1">Authentication Method</label>
            <select class="form-control" id="exampleFormControlSelect1">
              <option>1</option>
              <option>2</option>
              <option>3</option>
              <option>4</option>
              <option>5</option>
            </select>
            <small id="emailHelp" class="form-text text-muted">Authentication protocol user to login to the SMTP server</small>
          </div>
        </div>
        <div class="row mt-4" align="right">
          <div class="form-group col">
            <button class="btn btn-outline-success">Cancel</button>
            <button class="btn btn-success">Save</button>
          </div>
        </div>
      </div>
    </div>
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_admin')])
@endsection

@section('js')
  <script src="{{mix('js/admin/preferences/index.js')}}"></script>
@endsection