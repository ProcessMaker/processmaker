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
             <h4>Add your company logos</h4>
             <b-form>
              <div class="form-group row">
                <div class="col-4">
                  <label>Logo size must be 400x100. File format .jpg or .png</label>
                  <b-form-file @change="onImgUpload1(this)" accept="image/jpeg, image/png" v-model="file1" placeholder="Choose a file..."></b-form-file>
                </div>
                <div class="mt-3 col-6"><img class="img-1" src="#" id="file1Img"></div>
              </div>
              <div class="form-group row">
                <div class="col-6">
                  <label>Logo size must be 100x100. File format .jpg or .png</label>
                  <b-form-file @change="onImgUpload2(this)" accept="image/jpeg, image/png" v-model="file2" placeholder="Choose a file..."></b-form-file>
                </div>
                <div class="mt-3 col-6"><img class="img-2" src="#" id="file2Img"></div>
              </div>
              <h4>Create a color scheme to customize your UI</h4>
              <div>
                <div class="form-group">
                  <div class="color-select" @click="showPrimaryModal">
                    <span class="color-preview new-bg"></span>
                  </div>
                </div>
                <div class="form-group">
                  <div class="color-select" @click="showSecondaryModal">
                    <span class="color-preview new-bg"></span>
                  </div>
                </div>
             </b-form>
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
    <b-modal ref="primaryModal" title="Using Component Methods" align="center">
      <customize-color v-model="colors" @changeColor="changeColor"></customize-color>
      <div slot="modal-footer">
        <button @click="hidePrimaryModal" class="btn btn-outline-success btn-sm text-uppercase">
            Cancel
        </button>
        <button @click="showPrimaryModal" class="btn btn-success btn-sm text-uppercase">
            Save
        </button>
      </div>
    </b-modal>
    <b-modal ref="secondaryModal" title="Using Component Methods" align="center">
      <customize-color ></customize-color>
      <div slot="modal-footer">
        <button @click="hideSecondaryModal" class="btn btn-outline-success btn-sm text-uppercase">
            Cancel
        </button>
        <button @click="showSecondaryModal" class="btn btn-success btn-sm text-uppercase">
            Save
        </button>
      </div>
    </b-modal>
  </div>
@endsection

@section('js')
    <script src="{{mix('js/management/themes/index.js')}}"></script>
@endsection

<style lang="scss" scoped>
.color-select {
  border: 1px solid rgba(0, 0, 0, 0.125);
  width: 100%;
  border-radius: 0.125em;
  height: calc(1.875rem + 2px);
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
  width: 400px;
  height: 100px;
}
.img-2 {
  width: 100px;
  height: 100px;
}
.new-bg {
  background-color: #000;
}
</style>