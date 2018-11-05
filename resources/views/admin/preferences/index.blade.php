@extends('layouts.layout')

@section('title')
  {{__('Preferences')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
    <div class="container mt-4">
      <h3>Preferences</h3>
      <div class="card card-body">
          <h3 class="mt-1">Localization</h3>
        <div class="row">
          <div class="form-group col">
            {!!Form::label('timeZone', 'Time Zone');!!}
            {!!Form::select('timezone', $timezones, null, ['class'=> 'form-control']);!!}
          </div>
          <div class="form-group col">
            {!!Form::label('fullName', 'Full Name Format');!!}
            {!!Form::select('size', ['firstName' => 'First Name', 'lastName' => 'Last Name'], null, ['class'=> 'form-control']);!!}
            <small id="emailHelp" class="form-text text-muted">Format to display user's full name across all applications</small>
          </div>
        </div>
        <div class="row">
          <div class="form-group col">
            {!!Form::label('dateFormat', 'Global Date Format');!!}
            {!!Form::select('size', ['YY-DD-MM' => 'YY-DD-MM', 'MM-DD-YY' => 'MM-DD-YY', 'MM-DD-YYYY'=> 'MM-DD-YYYY', 'YYYY-DD-MM'=> 'YYYY-DD-MM'],null, ['class'=> 'form-control']);!!}
            <small id="emailHelp" class="form-text text-muted">Default Date Format. Dates across all applications will be displayed using this format</small>
          </div>
          <div class="form-group col">
            {!!Form::label('defaultLang', 'Default Language');!!}
            {!!Form::select('size', ['english' => 'English'], null, ['class'=> 'form-control']);!!}
            <small id="emailHelp" class="form-text text-muted">Default Language to be used across all applications</small>
          </div>
        </div>
          <h3 class="mt-3">Email Notifications</h3>
        <div class="row">
          <div class="form-group col">
            {!!Form::label('hostName', 'Host Name');!!}
            {!!Form::text('hostName', null, ['class'=> 'form-control', 'placeholder'=> 'Host Name']);!!}
            <small id="emailHelp" class="form-text text-muted">Address to the SMTP server used for email notifications</small>
          </div>
          <div class="form-group col">
            {!!Form::label('userName', 'Username');!!}
            {!!Form::text('userName', null, ['class'=> 'form-control', 'placeholder'=> 'Email Username']);!!}
            <small id="emailHelp" class="form-text text-muted">Enter the account to be authenticated against the SMTP server</small>
          </div>
        </div>
        <div class="row">
          <div class="form-group col">
            {!!Form::label('serverPort', 'Server Port');!!}
            {!!Form::text('serverPort', null, ['class'=> 'form-control', 'placeholder'=> 'Server Port']);!!}
            <small id="emailHelp" class="form-text text-muted">SMTP service port. Default port 25 will be used if you leave blank</small>
          </div>
          <div class="form-group col">
            {!!Form::label('password', 'Password');!!}
            {!!Form::password('password', ['class' => 'form-control', 'placeholder'=> 'Password']);!!}
            <small id="emailHelp" class="form-text text-muted">Password used to authenticate the user</small>
          </div>
        </div>
        <div class="row">
          <div class="form-group form-check col">
            {!!Form::checkbox('sslTls', 'SSL/TLS');!!}
            {!!Form::label('sslTls', 'SSL/TLS');!!}
            <small id="emailHelp" class="form-text text-muted">Enable if SSL/TLS is required by this server</small>
          </div>
          <div class="form-group col">
            {!!Form::label('authMethod', 'Authentication Method');!!}
            {!!Form::select('size', ['SSL' => 'SSL', 'GSSAPI' => 'GSSAPI', 'NTLM'=> 'NTLM', 'MD5'=> 'MD5', 'password'=> 'password'],null, ['class'=> 'form-control']);!!}
            <small id="emailHelp" class="form-text text-muted">Authentication protocol user to login to the SMTP server</small>
          </div>
        </div>
        <h3 class="mt-3">Logo Customization</h3>
        <div id="uicustomize">
          {{ Form::open() }}
            <div class="form-group row">
              <div class="col-6">
                {{ Form::label('icon', 'Logo size must be 400x100. File format .jpg or .png ') }}
                <div class="custom-file">
                  <input @change="onImgUpload1(this)" type="file" class="custom-file-input" id="customFile" accept="image/jpeg, image/png" v-model="file1" placeholder="Choose a file...">
                  <label class="custom-file-label" for="customFile">Choose file</label>
                </div>
              </div>
              <div class="col-6"><img class="img-1" src="#" id="file1Img"></div>
            </div>
            <div class="form-group row">
              <div class="col-6">
                {{ Form::label('icon', 'Logo size must be 100x100. File format .jpg or .png ') }}
                <div class="custom-file">
                  <input @change="onImgUpload2(this)" type="file" class="custom-file-input" id="customFile" accept="image/jpeg, image/png" v-model="file2" placeholder="Choose a file...">
                  <label class="custom-file-label" for="customFile">Choose file</label>
                </div>
              </div>
              <div class="col-6"><img class="img-2" src="#" id="file2Img"></div>
            </div>
          {{ Form::close() }}
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
  <script>
  new Vue({
    el: '#uicustomize',
    data: {
      file1: null,
      file2: null,
    },
    methods: {
      onImgUpload1(input) {
        let file = event.target.files[0];
        let reader = new FileReader();
        reader.onload = function (input) {
          $('#file1Img')
            .attr('src', reader.result);
        };
        reader.readAsDataURL(file);
      },
      onImgUpload2(input) {
        let file = event.target.files[0];
        let reader = new FileReader();
        reader.onload = function (input) {
          $('#file2Img')
            .attr('src', reader.result);
        };
        reader.readAsDataURL(file);
      }
    }
  })
</script>
@endsection
@section('css')
    <style lang="scss" scoped>
.color-select {
  border: 1px solid rgba(0, 0, 0, 0.125);
  width: 100%;
  border-radius: 0.125em;
  height: calc(1.875rem + 2px);
  display: flex;
}
.color-preview {
  height: calc(1.875rem + 2px);
  width: 20%;
   display: inline-block;
}
span {
  width: 50%;
  border-radius: 0.125em;
  height: calc(1.875rem + 2px);
}
.img-1 {
  max-width: 400px;
}
.img-2 {
  max-width: 100px;
}
.new-bg {
  background-color: #000;
}
</style>
@endsection