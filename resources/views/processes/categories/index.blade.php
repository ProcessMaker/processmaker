@extends('layouts.layout')

@section('title')
{{__('Process Categories')}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
<div class="container page-content" id="process-categories-listing">
    <h1>{{__('Process Categories')}}</h1>
    <div class="row">
        <div class="col">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text" id="basic-addon1">
                        <i class="fas fa-search"></i>
                    </span>
                </div>
                <input v-model="filter" class="form-control" placeholder="{{__('Search')}}...">
            </div>

        </div>
        <div class="col-8" align="right">
            <a href="#" @click="showModal" class="btn btn-action" data-toggle="modal" data-target="#createGroup"><i class="fas fa-plus"></i>
                {{__('Category')}}</a>
        </div>
    </div>
    <div class="container-fluid">
        <modal-category-add-edit ref="addEdit" :input-data="formData" @reload="reload"></modal-category-add-edit>
        <categories-listing ref="list" @edit="editCategory" @delete="deleteCategory" :filter="filter"></categories-listing>
    </div>
</div>
@endsection

@section('js')
<script src="{{mix('js/processes/categories/index.js')}}"></script>
@endsection