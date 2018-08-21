@extends('layouts.layout', ['title' => __('UI Customization')])

@section('sidebar')
  @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
<div id="uicustomize" class="container page-content">
    <h4>UI Customization</h4>
    <br/>
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
            <input type="text" class="form-control inline-input">
        </div>

        <div class="form-group">
            <label>Enter hex color or chose a color for the action buttons</label>
            <input type="text" class="form-control inline-input">
        </div>
    
    <div>
        <span>
            <div>

            </div>
        </span>
        <customize-color></customize-color>
    </div>
</div>
@endsection

@section('js')
    <script src="{{mix('js/management/admin/index.js')}}"></script>
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