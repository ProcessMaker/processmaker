    <div class="px-3 page-content" id="scriptIndex">
        <div id="search-bar" class="search mt-2 bg-light p-2" vcloak>
            <div class="d-flex">
                <div class="flex-grow-1">
                    <div id="search" class="pr-2">
                        <input v-model="filter" class="form-control" placeholder="{{__('Search')}}...">
                    </div>
                </div>
                <div class="flex-shrink-0">
                    <button title="" type="button" class="btn btn-primary" data-original-title="Search"><i class="fas fa-search"></i></button>
                    @can('create-scripts')
                        <a href="#" id="create_script" class="btn btn-secondary" @click="$refs.addScript.show()">
                            <i class="fas fa-plus"></i>
                            {{__('Script')}}
                        </a>
                    @endcan
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <script-listing :filter="filter"
                            :script-formats='@json($config->scriptFormats)'
                            :permission="{{ \Auth::user()->hasPermissionsFor('scripts') }}"
                            ref="listScript"
                            @delete="deleteScript">
            </script-listing>
        </div>

        @can('create-scripts')
            <b-modal hidden 
                    ref="addScript" 
                    title="{{ __('Create Script') }}" 
                    ok-title="{{ __('Save') }}" 
                    centered
                    @ok="onSubmit" 
                    @hidden="onClose">
                @if ($config->countCategories !== 0)
                    <div class="form-group">
                        {!!Form::label('title', __('Name'))!!}
                        {!!Form::text('title', null, ['class'=> 'form-control', 'v-model'=> 'addScript.title', 'v-bind:class' =>
                        '{\'form-control\':true, \'is-invalid\':addScript.addError.title}'])!!}
                        <small class="form-text text-muted" v-if="!addScript.addError.title">{{ __('The script name must be distinct.') }}</small>
                        <div class="invalid-feedback" v-for="title in addScript.addError.title">@{{title}}</div>
                    </div>
                    <div class="form-group">
                        {!!Form::label('description', __('Description'))!!}
                        {!!Form::textarea('description', null, ['rows'=>'2','class'=> 'form-control', 'v-model'=> 'addScript.description',
                        'v-bind:class' => '{\'form-control\':true, \'is-invalid\':addScript.addError.description}'])!!}
                        <div class="invalid-feedback" v-for="description in addScript.addError.description">@{{description}}</div>
                    </div>
                    <category-select :label="$t('Category')" 
                                    api-get="script_categories"
                                    api-list="script_categories" 
                                    v-model="addScript.script_category_id"
                                    :errors="addScript.addError.script_category_id">
                    </category-select>
                    <div class="form-group">
                        {!!Form::label('language', __('Language'))!!}
                        {!!Form::select('language', [''=>__('Select')] + $config->scriptFormats, null, ['class'=>
                        'form-control', 'v-model'=> 'addScript.language', 'v-bind:class' => '{\'form-control\':true,
                        \'is-invalid\':addScript.addError.language}']);!!}
                        <div class="invalid-feedback" v-for="language in addScript.addError.language">@{{language}}</div>
                    </div>

                    <div class="form-group">
                        <label class="typo__label">{{__('Run script as')}}</label>
                        <select-user v-model="addScript.selectedUser" 
                                    :multiple="false"
                                    :class="{'is-invalid': addScript.addError.run_as_user_id}">
                        </select-user>
                        <small class="form-text text-muted"
                                v-if="!addScript.addError.run_as_user_id">{{__('Select a user to set the API access of the Script')}}</small>
                        <div class="invalid-feedback" v-for="run_as_user_id in addScript.addError.run_as_user_id"> @{{run_as_user_id}}</div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('timeout', __('Timeout')) !!}
                        <div class="form-row ml-0">
                            {!! Form::text('timeout', null, ['id' => 'timeout', 'class'=> 'form-control col-2',
                            'v-model' => 'addScript.timeout', 'pattern' => '[0-9]*', 'v-bind:class' => '{"form-control":true, "is-invalid":addScript.addError.timeout}']) !!}
                            {!! Form::range(null, null, ['id' => 'timeout-range', 'class'=> 'custom-range col ml-1 mt-2',
                            'v-model' => 'addScript.timeout', 'min' => 0, 'max' => 300]) !!}
                            <div class="invalid-feedback" v-for="timeout in addScript.addError.timeout">@{{timeout}}</div>
                        </div>
                        <small class="form-text text-muted" v-if="!addScript.addError.timeout">
                            {{ __('Enter how many seconds the Script runs before timing out (0 is unlimited).') }}
                        </small>
                    </div>
                @else
                    <div>{{__('Categories are required to create a script')}}</div>
                    <a href="{{ url('designer/scripts/categories') }}" class="btn btn-primary container mt-2">
                        {{__('Add Category')}}
                    </a>
                @endif
                <template v-slot:modal-footer>
                    <button type="button" class="btn btn-outline-secondary" @click="$refs.addScript.hide()">
                        {{__('Cancel')}}
                    </button>
                    @if ($config->countCategories !== 0)
                        <button type="button" class="btn btn-secondary ml-2" @click="onSubmit">
                            {{__('Save')}}
                        </button>
                    @endif
                </template>
            </b-modal>
        @endcan
    </div>

@section('js')
    <script src="{{mix('js/processes/scripts/index.js')}}"></script>
@append
