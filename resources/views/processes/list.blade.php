<div class="px-3 page-content" id="processIndex">
    <div id="search-bar" class="search mt-2 bg-light" vcloak>
        <div class="d-flex">
            <div class="flex-grow-1">
                <div id="search" class="pr-2">
                    <input v-model="filter" class="form-control" placeholder="{{__('Search')}}...">
                </div>
            </div>
            <div class="flex-shrink-0">
                <button title="" type="button" class="btn btn-primary" data-original-title="Search"><i
                        class="fas fa-search"></i></button>
                @can('import-processes')
                    <a href="#" id="import_process" class="btn btn-outline-secondary" @click="goToImport">
                        <i class="fas fa-file-import"></i> {{__('Import')}}
                    </a>
                @endcan
                @can('create-processes')
                    <a href="#" id="create_process" class="btn btn-secondary" @click="$refs.addProcess.show()">
                        <i class="fas fa-plus"></i> {{__('Process')}}
                    </a>
                @endcan
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <processes-listing
            ref="processListing"
            :filter="filter"
            status="{{ $config->status }}"
            v-on:edit="edit"
            v-on:reload="reload"
            :permission="{{ \Auth::user()->hasPermissionsFor('processes') }}"
        ></processes-listing>
    </div>

    <b-modal
        hidden
        ref="addProcess"
        title="{{__('Create Process')}}"
        ok-title="{{__('Save')}}"
        @ok="onSubmit",
        @hidden="onClose"
    >
                @if ($config->countCategories!== 0)
                        <div class="form-group">
                            {!! Form::label('name', __('Name')) !!}
                            {!! Form::text('name', null, [
                            'autocomplete' => 'off',
                            'class'=> 'form-control',
                            'v-model'=> 'processForm.name',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':processForm.addError.name}']) !!}
                            <small class="form-text text-muted"
                                   v-if="! processForm.addError.name">{{ __('The process name must be distinct.') }}</small>
                            <div class="invalid-feedback" v-for="name in processForm.addError.name">@{{name}}</div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('description', __('Description')) !!}
                            {!! Form::textarea('description', null, [
                            'class'=> 'form-control',
                            'rows' => '3',
                            'v-model'=> 'processForm.description',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':processForm.addError.description}']) !!}
                            <div class="invalid-feedback" v-for="description in processForm.addError.description">
                                @{{description}}
                            </div>
                        </div>
                        <category-select :label="$t('Category')" api-get="process_categories"
                                         api-list="process_categories" v-model="processForm.process_category_id"
                                         :errors="processForm.addError.process_category_id">
                        </category-select>
                        <div class="form-group">
                            {!! Form::label('fileName', __('Upload BPMN File (optional)')) !!}
                            <div class="input-group">
                                <input type="text" name="fileName" class="form-control" v-model="processForm.selectedFile">
                                <button type="button" @click="browse" class="btn btn-secondary"><i
                                        class="fas fa-upload"></i>
                                    {{__('Upload file')}}
                                </button>
                                <input type="file" class="custom-file-input"
                                       :class="{'is-invalid': processForm.addError.bpmn && processForm.addError.bpmn.length}"
                                       ref="customFile" @change="onFileChange" accept=".bpmn" style="height: 1em;">
                                <div class="invalid-feedback" v-for="error in processForm.addError.bpmn">@{{error}}</div>
                            </div>
                        </div>
                @else
                        <div>{{__('Categories are required to create a process')}}</div>
                        <a href="{{ url('designer/processes/categories') }}" class="btn btn-primary container mt-2">
                            {{__('Add Category')}}
                        </a>
                @endif
    </b-modal>
</div>

@section('js')
    <script src="{{mix('js/processes/index.js')}}"></script>
@append
