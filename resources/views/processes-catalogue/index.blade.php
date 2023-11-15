@extends('layouts.layout')

@section('title')
    {{__('Processes Catalogue')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_processes_catalogue')])
@endsection

@section('content')
  <div class="px-3 page-content mb-0" id="processes-catalogue">
    <processes-catalogue></processes-catalogue>
  </div>
@endsection

@section('js')
    <script src="{{mix('js/processes-catalogue/index.js')}}"></script>
@endsection

@section('css')
    <style>
        .has-search .form-control {
            padding-left: 2.375rem;
        }

        .has-search .form-control-feedback {
            position: absolute;
            z-index: 2;
            display: block;
            width: 2.375rem;
            height: 2.375rem;
            line-height: 2.375rem;
            text-align: center;
            pointer-events: none;
            color: #aaa;
        }

        .card-border {
            border-radius: 4px !important;
        }

        .card-size-header {
            width: 90px;
        }

        .option__image {
            width: 27px;
            height: 27px;
            border-radius: 50%;
        }

        .initials {
            display: inline-block;
            text-align: center;
            font-size: 12px;
            max-width: 25px;
            max-height: 25px;
            min-width: 25px;
            min-height: 25px;
            border-radius: 50%;
        }
    </style>
@endsection
