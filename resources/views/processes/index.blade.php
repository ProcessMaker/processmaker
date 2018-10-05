@extends('layouts.layout', ['title' => __('Processes Management')])

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
<div class="container mt-4" id="processIndex">
    <div class="row mt-5">
        <div class="col-2">
            <h3>{{__('Processes')}}</h3>  
        </div>
        <div class="col-4" style="margin-left: -67px; margin-right: 21px;">
            <input type="text" class="form-control" placeholder="&#xf0e0; Search">
        </div>
        <div class="col-5"></div>
        <div class="col">
            <button class="btn btn-secondary"><i class="fas fa-plus"></i> process</button>
        </div>
    </div>
    <table class="table table-hover mt-4 vuetable" id="app">
        <thead>
            <tr>
                <th scope="col">process<i class="sort-icon fas fa-sort ml-2"></i></th>
                <th scope="col">category<i class="sort-icon fas fa-sort ml-2"></i></th>
                <th scope="col">status<i class="sort-icon fas fa-sort ml-2"></i></th>
                <th scope="col">modified by <i class="sort-icon fas fa-sort ml-2"></i></th>
                <th scope="col">created <i class="sort-icon fas fa-sort ml-2"></i></th>
                <th scope="col">modified <i class="sort-icon fas fa-sort ml-2"></i></th>
                <th scope="col"></th>
            </tr>
        </thead>
        <tbody>
        @foreach ($processes as $process)
            <tr @mouseover="showButtons" @mouseout="seen=false">
                <td scope="row">{{$process->name}}</td>
                <td>HR</td>
                <td class="text-uppercase"><i class="fas fa-circle text-success small"></i> active</td>
                <td><img src="../avatar-placeholder.gif" style="height: 20px; border-radius: 50%;" /> Name Name</td>
                <td>01/01/1111 11:11</td>
                <td>02/02/2222 22:22</td>
                <td class="vuetable-slot">
                    <div class="actions" v-if="seen">
                        <div class="popout">
                            <i class="fas fa-edit btn"></i><i class="fas fa-stop-circle btn"></i><i class="fas fa-eye btn"></i><i
                                class="fas fa-trash-alt btn"></i>
                        </div>
                    </div>
                </td>
            </tr>
        @endforeach  
        </tbody>
    </table>
    <div class="text-secondary">1 - 4 of 4 Processes</div>
</div>
@endsection

@section('js')
    <script src="{{mix('js/processes/index.js')}}"></script>
@endsection
