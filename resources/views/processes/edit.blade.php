@extends('layouts.layout', ['title' => __('Processes Management')])

@section('title')
  {{__('Edit Process')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
<!doctype html>

<div class="container">
    <h1>Edit Process</h1>
    <div class="row">
        <div class="col-8">
            <div class="card card-body">
                
                <div class="form-group">
                    {!!Form::label('processTitle', __('Process title'))!!}
                    {!!Form::text('processTitle', null,
                        ['class'=> 'form-control',
                            'v-model'=> 'formData.name',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.username}'
                        ])
                    !!}
                    <div class="invalid-feedback" v-if="errors.processTitle">@{{errors.name[0]}}</div>
                </div>
                <div class="form-group">
                    {!! Form::label('description', 'Description') !!}
                    {!! Form::textarea('description', null,
                        ['id' => 'description',
                            'rows' => 4,
                            'class'=> 'form-control',
                            'v-model' => 'formData.description',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.description}'
                        ])
                    !!}
                    <div class="invalid-feedback" v-if="errors.description">@{{errors.description[0]}}</div>
                </div>
                <div class="form-group p-0">
                    {!! Form::label('category', 'Category') !!}
                    {!! Form::select('status', $categories, null,
                        ['id' => 'status',
                            'class' => 'form-control',
                            'v-model' => 'formData.status',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.status}'
                        ])
                    !!}
                    <div class="invalid-feedback" v-if="errors.category">@{{errors.category[0]}}</div>
                </div>
                <div class="form-group p-0">
                    {!! Form::label('summaryScreen', 'Summary Screen') !!}
                    {!! Form::select('summaryScreen', $screens, null,
                        ['id' => 'summaryScreen',
                            'class' => 'form-control',
                            'v-model' => 'formData.status',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.status}'
                        ])
                    !!}
                </div>
                <div class="form-group p-0">
                    {!! Form::label('status', 'Status') !!}
                    {!! Form::select('status', ['ACTIVE' => 'Active', 'INACTIVE' => 'Inactive'], null,
                        ['id' => 'status',
                        'class' => 'form-control',
                        'v-model' => 'formData.status',
                        'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.status}'])
                    !!}
                    <div class="invalid-feedback" v-if="errors.status">@{{errors.status[0]}}</div>
                </div>
                <div class="d-flex justify-content-end mt-2">
                    {!! Form::button('Cancel', ['class'=>'btn btn-outline-success', '@click' => 'onClose']) !!}
                    {!! Form::button('Update', ['class'=>'btn btn-success ml-2', '@click' => 'onUpdate']) !!}

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

@endsection

@section('js')
    <script src="{{mix('js/processes/index.js')}}"></script>
@endsection