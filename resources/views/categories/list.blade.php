@if ($config->permissions['view'])
<div class="page-content mb-0" id="categories-listing">
    <div id="search-bar" class="search mb-3" vcloak>
        <div class="d-flex flex-column flex-md-row">
            <div class="flex-grow-1">
                <div id="search" class="mb-3 mb-md-0">
                    <div class="input-group w-100">
                        <input v-model="filter" class="form-control" placeholder="{{ __('Search') }}">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-primary" data-original-title="Search">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @if ($config->permissions['create'])
                <div class="d-flex ml-md-2 flex-column flex-md-row">
                    <button type="button" id="create_category" class="btn btn-secondary" data-toggle="modal"
                            data-target="#createCategory" @click="emptyData">
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
        <div class="modal fade" tabindex="-1" role="dialog" id="createCategory" data-backdrop="static">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">@{{ getTitle() }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="onClose">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            {!!Form::label('name', __('Category Name'))!!}
                            {!!Form::text('name', null, ['class'=> 'form-control', 'v-model'=> 'name',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.name}'])!!}
                            <small class="form-text text-muted" v-if="! errors.name">
                                {{ __('The category name must be distinct.') }}
                            </small>
                            <div class="invalid-feedback" v-for="name in errors.name">@{{name}}</div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('status', __('Status')) !!}
                            {!! Form::select('status', ['ACTIVE' => __('active'), 'INACTIVE' => __('Inactive')], null, ['id' => 'status',
                            'class' => 'form-control', 'v-model' => 'status', 'v-bind:class' => '{"form-control":true, "is-invalid":errors.status}']) !!}
                            <div class="invalid-feedback" v-for="status in errors.status">@{{status}}</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal" @click="onClose">
                            {{ __('Cancel') }}
                        </button>
                        <button type="button" class="btn btn-secondary" @click="onSubmit" :disabled="disabled">
                            {{ __('Save') }}
                        </button>
                    </div>
                </div>

            </div>
        </div>
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
            $("#createCategory").modal("show");
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
                $("#createCategory").modal("hide");
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