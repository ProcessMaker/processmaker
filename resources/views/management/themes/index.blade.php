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
                <label>Add your full size company logo</label>
                <b-form-file v-model="file" :state="Boolean(file)" placeholder="Logo size must be 400x100. File format .jpg or .png"></b-form-file>
                <label>Add your company icon</label>
                <b-form-file v-model="file" :state="Boolean(file)" placeholder="Logo size must be 400x100. File format .jpg or .png"></b-form-file>
            </form>
            <h6 style="mt-5">Create a color scheme to customize your UI</h6>
            <div class="form-group">
                <label>Enter hex color or chose a color for the left navigation bar</label>
                <input type="text" class="form-control inline-input dropdown-toggle" id="pickColor1" @click=keepPickColor data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <div class="dropdown-menu" aria-labelledby="pickColor1">
                    <customize-color ></customize-color>
                </div>
            </div>

            <div class="form-group">
                <label>Enter hex color or chose a color for the action buttons</label>
                <input type="text" class="form-control inline-input dropdown show" id="pickColor2"  data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <customize-color class="dropdown-menu p-0" aria-labelledby="pickColor2" multiple  ></customize-color>
            </div>
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

</style>