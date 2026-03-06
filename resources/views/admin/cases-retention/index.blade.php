@extends('layouts.layout')

@section('title')
    {{__('Cases Retention')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Admin') => route('admin.index'),
        __('Cases Retention') => null,
    ]])
@endsection
@section('content')
    <div class="px-3" id="cases-retention">
        
    </div>

    <div class="page-content px-3 mb-0" id="casesRetentionIndex">
        <div class="card p-3 cases-retention-container">
            <h5 class="mb-3">{{ __('Cases Retention Logs') }}</h5>
            <div id="search-bar" class="search mb-3" vcloak>
                <div class="d-flex flex-column flex-md-row">
                    <div class="flex-grow-1 d-flex">
                        <div id="search" class="mb-3 mb-md-0 w-100 mr-3">
                            <div class="input-group w-100">
                                <div class="input-group-prepend d-flex align-items-center pl-3 pr-1 search">
                                    <i class="fas fa-search"></i>
                                </div>
                                <input
                                    id="search-box"
                                    v-model="filter"
                                    class="form-control search-box"
                                    placeholder="{{__('Search here')}}"
                                    aria-label="{{__('Search here')}}"
                                    data-test="input-search">
                            </div>
                        </div>
                        <button
                            class="btn btn-primary download-retention-logs text-nowrap"
                            @click="downloadRetentionLogs"
                            data-test="download-retention-logs-button">
                            <i class="fas fa-download mr-1 mt-1" style="font-size: 14px;"></i>
                            <span >{{ __('Download Logs') }}</span>
                        </button>
                    </div>
                </div>
            </div>
            <cases-retention-logs
                ref="casesRetentionLogs"
                :filter="filter"
                v-on:reload="reload">
            </cases-retention-logs>
        </div>

    </div>
@endsection


@section('js')
    <script src="{{mix('js/admin/cases-retention/index.js')}}"></script>
@endsection

@section('css')
    <style>
        .cases-retention-container {
            border-radius: 8px;
            box-shadow: 0 0 9px 3px rgba(0, 0, 0, 0.04);
            border-color: #D7DDE5
        }
        .search input{
            border-radius: 8px;
            box-shadow: 0 3px 6px -3px rgba(0, 0, 0, 0.05);
            border-color: #d7dde5;
            border-left: 0;
        }
        .btn.download-retention-logs {
            text-transform: none;
            border-radius: 8px;
        }
        .input-group-prepend.search {
            border: 1px solid #d7dde5;
            border-radius: 8px 0 0 8px;
            border-right: 0;
            color: #596372;
        }
    </style>
@endsection