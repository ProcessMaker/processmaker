@extends('layouts.layout', ['title' => __('UI Customization')])

@section('sidebar')
  @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
<div id="uicustomize" class="container">
  <div class="container">
    <h1>UI Customization</h1>
    <div class="row">
      <div class="col-8">
        <div class="card card-body">
            <form>
                <b-form-file v-model="file" :state="Boolean(file)" placeholder="Logo size must be 400x100. File format .jpg or .png"></b-form-file>
                <b-form-file v-model="file" :state="Boolean(file)" placeholder="Logo size must be 400x100. File format .jpg or .png"></b-form-file>
            </form>
      </div>
    </div>
      <div class="col-4">
        <div class="card card-body">
        Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
      </div>
    </div>
  </div>
</div>  
</div>
@endsection

@section('js')
    <script src="{{mix('js/management/themes/index.js')}}"></script>
@endsection

<style lang="scss" scoped>
.inline-button {
  background-color: rgb(109,124,136);
  font-weight: 100;
}
.text-mute {
    color: #788793;
}
.custom-file {
    height: auto !important;
    margin-bottom: 15px !important;
}
.custom-file-label::after {
    content: "CHOOSE" !important;
    color: white !important;
    background-color: rgb(173, 183, 191) !important;
}
</style>