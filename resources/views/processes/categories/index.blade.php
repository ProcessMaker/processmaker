@extends('layouts.layout', ['title' => __('Processes Management')])

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_process')])
@endsection

@section('content')
    <div class="container page-content" id="process-categories-listing">
        <!-- Task Add Dialog -->
        <div class="row">
            <div class="col-sm-12">
                <div class="row">
                    <div class="col-md-8 d-flex align-items-center col-sm-12">
                        <h1 class="page-title">{{__('Process Categories')}}</h1>
                    </div>
                    <div class="col-md-4 d-flex justify-content-end align-items-center col-sm-12 actions">
                        <a href="#"  @click="processModal=true" class="btn btn-action"><i class="fas fa-plus"></i> {{__('Category')}}</a>
                    </div>
                </div>
                <!-- <modal-create-process :show="processModal" @close="processModal=false"></modal-create-process> -->
                <categories-listing :filter="filter"></categories-listing>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{mix('js/processes/categories/index.js')}}"></script>
@endsection
