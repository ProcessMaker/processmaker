    <div class="px-3 page-content" id="screenIndex">
        <div id="search-bar" class="search mt-2 bg-light p-2" vcloak>
            <div class="d-flex">
                <div class="flex-grow-1">
                    <div id="search" class="pr-2">
                        <input v-model="filter" class="form-control" placeholder="{{__('Search')}}...">
                    </div>
                </div>
                <div class="flex-shrink-0">
                    <button title="" type="button" class="btn btn-primary" data-original-title="Search"><i class="fas fa-search"></i></button>
                    @can('import-screen')
                        <a href="#" class="btn btn-outline-secondary" @click="goToImport"><i class="fas fa-file-import"></i>
                            {{__('Import')}}</a>
                    @endcan
                    @can('create-screens')
                        <button type="button" href="#" id="create_screen" class="btn btn-secondary" @click="$refs.createScreen.show()">
                            <i class="fas fa-plus"></i> {{__('Screen')}}
                        </button>
                    @endcan
                </div>
            </div>
        </div>

        <screen-listing ref="screenListing"
                        :filter="filter"
                        :permission="{{ \Auth::user()->hasPermissionsFor('screens') }}"
                        v-on:reload="reload">
        </screen-listing>

    @can('create-screens')
      <b-modal hidden 
               ref="createScreen" 
               title="{{ __('Create Screen') }}"
               ok-title="{{ __('Save') }}" 
               centered
      >
        @if ($config->countCategories !== 0)
          <div class="form-group">
            {!! Form::label('title', __('Name')) !!}
            {!! Form::text('title', null, ['id' => 'title','class'=> 'form-control', 'v-model' => 'addScreen.formData.title',
            'v-bind:class' => '{"form-control":true, "is-invalid":addScreen.errors.title}']) !!}
            <small class="form-text text-muted" v-if="!addScreen.errors.title">
              {{ __('The screen name must be distinct.') }}
            </small>
            <div class="invalid-feedback" v-for="title in addScreen.errors.title">@{{title}}</div>
          </div>
          <div class="form-group">
            {!! Form::label('type', __('Type')) !!}
            {!! Form::select('type', [null => __('Select')] + $config->types, '', ['id' => 'type','class'=> 'form-control', 'v-model' => 'addScreen.formData.type',
            'v-bind:class' => '{"form-control":true, "is-invalid":addScreen.errors.type}']) !!}
            <div class="invalid-feedback" v-for="type in addScreen.errors.type">@{{type}}</div>
          </div>
          <div class="form-group">
            {!! Form::label('description', __('Description')) !!}
            {!! Form::textarea('description', null, ['id' => 'description', 'rows' => 4, 'class'=> 'form-control',
            'v-model' => 'addScreen.formData.description', 'v-bind:class' => '{"form-control":true, "is-invalid":addScreen.errors.description}']) !!}
            <div class="invalid-feedback" v-for="description in addScreen.errors.description">@{{description}}</div>
          </div>
          <category-select :label="$t('Category')" api-get="screen_categories" api-list="screen_categories" v-model="addScreen.formData.screen_category_id" :errors="addScreen.errors.screen_category_id">
          </category-select>
      @else
        <div>{{__('Categories are required to create a screen')}}</div>
        <a href="{{ url('designer/screens/categories') }}" class="btn btn-primary container mt-2">
            {{__('Add Category')}}
        </a>
      @endif
      
        <template #modal-footer>
          <button type="button" class="btn btn-outline-secondary" @click="$refs.createScreen.hide()">
              {{__('Cancel')}}
          </button>

          @if ($config->countCategories !== 0)
            <button type="button" @click="onSubmit" class="btn btn-secondary ml-2" :disabled="addScreen.disabled">
                {{__('Save')}}
            </button>  
          @endif
        </template>
      </b-modal>
    @endcan
  </div>

@section('js')
  <script src="{{mix('js/processes/screens/index.js')}}"></script>
@append
