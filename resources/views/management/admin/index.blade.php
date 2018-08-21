@extends('layouts.layout', ['title' => __('UI Customization')])

@section('sidebar')
  @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
<div id="uicustomize" class="container page-content">
    <h4>UI Customization</h4>
    <h5>Add your company logo</h5>
    <form>
        <div class="custom-file">
            <input type="file" class="custom-file-input" id="validatedCustomFile" required>
            <label class="custom-file-label" for="validatedCustomFile">Choose file...</label>
            <small class="">Logo size must be 400x100. File format .jpg or .png</small>
        </div>

        <div class="custom-file">
            <input type="file" class="custom-file-input" id="validatedCustomFile" required>
            <label class="custom-file-label" for="validatedCustomFile">Choose file...</label>
            <small class="">Logo size must be 400x100. File format .jpg or .png</small>
        </div>
        <h5 style="mt-5">Create a color scheme to customize your UI</h5>
        <div class="form-group">
            <label>Enter hex color or chose a color for the left navigation bar</label>
            <input type="text" class="form-control inline-input">
        </div>

        <div class="form-group">
            <label>Enter hex color or chose a color for the action buttons</label>
            <input type="text" class="form-control inline-input">
        </div>
        
    </form>
    <div>
        <p>Create a color scheme to customize your UI</p>
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