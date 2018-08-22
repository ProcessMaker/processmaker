@extends('layouts.layout', ['title' => __('UI Customization')])

@section('sidebar')
  @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
<div id="uicustomize" class="container">
    <h1>UI Customization</h1>
    <div class="row">
        <div class="col-8">
        <h6>Add your company logo</h6>
            <form>
                <div class="custom-file">
                    <input type="file" class="custom-file-input" id="validatedCustomFile" required>
                    <label class="custom-file-label" for="validatedCustomFile">Choose file...</label>
                    <small class="text-mute">Logo size must be 400x100. File format .jpg or .png</small>
                </div>

                <div class="custom-file">
                    <input type="file" class="custom-file-input" id="validatedCustomFile" required>
                    <label class="custom-file-label" for="validatedCustomFile">Choose file...</label>
                    <small class="text-mute">Logo size must be 400x100. File format .jpg or .png</small>
                </div>
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