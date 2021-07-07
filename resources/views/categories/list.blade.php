@if ($config->permissions['view'])
<div class="page-content mb-0" id="categories-listing">
    <div id="search-bar" class="search mb-3" vcloak>
        <div class="d-flex flex-column flex-md-row">
            <div class="flex-grow-1">
                <div id="search" class="mb-3 mb-md-0">
                    <div class="input-group w-100">
                        <input id="search-box" v-model="filter" class="form-control" placeholder="{{ __('Search') }}" aria-label="{{__('Search')}}">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-primary" aria-label="{{__('Search')}}">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @if ($config->permissions['create'])
                <div class="d-flex ml-md-2 flex-column flex-md-row">
                    <button type="button" class="btn btn-secondary" @click="showModal()" aria-label="{{ __('Create Category') }}" aria-haspopup="dialog">
                        <i class="fas fa-plus"></i> {{ __('Category') }}
                    </button>
                </div>
            @endif
        </div>
    </div>

    <categories-listing
        ref="list"
        @reload="reload"
        :filter="filter"
        :permissions="{{ json_encode($config->permissions) }}"
        api-route="{{route($config->routes->categoryListApi)}}"
        load-on-start="{{$config->showCategoriesTab ?? true}}"
        include="{{$config->apiListInclude}}"
        label-count="{{$config->labels->countColumn}}"
        @edit="edit"
        count="{{$config->countField}}">
    </categories-listing>

    @if ($config->permissions['create'] || $config->permissions['edit'])
        <pm-modal ref="createCategoryModal" id="createCategoryModal" :title="getTitle()" @hidden="onClose" @ok.prevent="onSubmit" :ok-disabled="disabled" style="display: none;">
            <div class="form-group">
                {!!Form::label('category-name', __('Category Name'))!!}<small class="ml-1">*</small>
                {!!Form::text('name', null, ['class'=> 'form-control', 'v-model'=> 'name', 'id' => 'category-name',
                'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.name}'])!!}
                <small class="form-text text-muted" v-if="! errors.name">
                    {{ __('The category name must be distinct.') }}
                </small>
                <div class="invalid-feedback" v-for="name in errors.name">@{{name}}</div>
            </div>
            <div class="form-group">
                {!! Form::label('status', __('Status')) !!}
                {!! Form::select('status', ['ACTIVE' => __('Active'), 'INACTIVE' => __('Inactive')], null, ['id' => 'status',
                'class' => 'form-control', 'v-model' => 'status', 'v-bind:class' => '{"form-control":true, "is-invalid":errors.status}']) !!}
                <div class="invalid-feedback" v-for="status in errors.status">@{{status}}</div>
            </div>
        </pm-modal>
    @endif

</div>

@section('js')
    <script src="{{mix('js/processes/categories/index.js')}}"></script>
    <script>
      ProcessMaker.CategoriesIndex = new Vue({
        el: "#categories-listing",
        data: {
          filter: "",
          formData: null,
          errors: {},
          id: "",
          name: "",
          status: "ACTIVE",
          disabled: false,
          route: @json(route($config->routes->categoryListApi))
        },
        methods: {
          emptyData () {
            this.id = "";
            this.name = "";
            this.status = "ACTIVE";
            this.disabled = false;
            this.errors = {};
          },
          getTitle () {
            return this.id ? this.$t("Edit Category") : this.$t("Create Category");
          },
          reload () {
            this.$refs.list.fetch();
          },
          edit (value) {
            this.emptyData();
            this.id = value.id;
            this.name = value.name;
            this.status = value.status;
            this.$refs.createCategoryModal.show();
          },
          showModal() {
            this.emptyData();
            this.$refs.createCategoryModal.show();
          },
          onClose () {
            this.emptyData();
          },
          onSubmit () {
            this.errors = {};
            // single click
            if (this.disabled) {
              return;
            }
            this.disabled = true;
            let method = "POST",
              url = this.route;
            if (this.id) {
              // Do an update
              method = "PUT";
              url = `${url}/${this.id}`;
            }
            ProcessMaker.apiClient({
              method,
              url,
              baseURL: "/",
              data: {
                name: this.name,
                status: this.status
              }
            })
              .then((response) => {
                this.$refs.createCategoryModal.hide();
                let message = "The category was created.";
                if (this.id) {
                  message = "The category was saved.";
                }
                ProcessMaker.alert(this.$t(message), "success");
                this.emptyData();
                this.reload();
              }).catch((error) => {
              this.disabled = false;
              if (error.response.status === 422) {
                this.errors = error.response.data.errors;
              }
            });
          }
        }
      });
    </script>
@append
@endif
