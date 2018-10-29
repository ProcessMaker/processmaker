@extends('layouts.layout')

@section('title')
    {{__('Edit Task')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')

    <div id="task" class="d-flex container mt-3">
        <div class="col-9">
            <h2>Task: {{$task->element_name}}</h2>
            <div class="card card-body border-0">
                <task-form process-id="{{$task->processRequest->process->getKey()}}"
                           instance-id="{{$task->processRequest->getKey()}}"
                           token-id="{{$task->getKey()}}"
                           :form="{{json_encode($task->getForm()->config)}}"
                           :data="{{json_encode($task->processRequest->data)}}" />
            </div>
            <div style="margin-top: 68px;">
                <div class="row">
                    <div class="col">
                        <div class="card card-body border-0">
                            <div align="center" style="color: #788793;">
                                You have not posted any comments yet.
                            </div>
                            <div class="row mt-3">
                                <div class="col-1">
                                    <!-- img class="mr-2" src="../avatar-placeholder.gif" style="height: 45px; border-radius: 50%;"/ -->
                                </div>
                                <div class="form-group col">
                                    <textarea class="form-control" id="exampleFormControlTextarea1" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-10"></div>
                                <div class="form-group col text-right">
                                    <button class="btn btn-success">comment</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-3">

            <div class="list-group">
                <div class="list-group-item list-group-item-action bg-secondary text-light">
                </div><div class="list-group">
                    <div class="list-group-item list-group-item-action bg-secondary text-light"><h3>{{__('Completed')}}</h3></div>
                    <div class="list-group-item list-group-item-action">
                        <i class="far fa-calendar-alt fa-lg"></i> {{$task->due_at->diffForHumans()}}
                        <br />
                        {{$task->due_at->format(config('app.dateformat'))}}
                    </div>
                    <div class="list-group-item list-group-item-action"><h4>{{__('Assigned To')}}</h4> <br />
                        <img src="https://via.placeholder.com/40" style="border-radius: 50%;"> Jane Manager
                    </div>

                    <div class="list-group-item list-group-item-action">
                        <i class="far fa-calendar-alt fa-lg"></i> {{__('Assigned 999 days ago')}}
                        <br />
                        <h4>10/12/17 18:25</h4>
                    </div>

                    <div class="list-group-item list-group-item-action"><h4>{{__('Request')}}</h4> <br />
                        <a href="http://www.processmaker.com"> #39393 Dummy process link </a>
                    </div>

                    <div class="list-group-item list-group-item-action"><h4>{{__('Assigned To')}}</h4> <br />
                        <img src="https://via.placeholder.com/40" style="border-radius: 50%;"> Alonso Requestor
                    </div>

                </div>

            </div>
        </div>
        @endsection

        @section('js')
            <script src="{{mix('js/tasks/show.js')}}"></script>
        @endsection

        @section('css')
            <style lang="scss" scoped>

                .taskNav {
                    height:40px;
                    background-color:#b6bfc6;
                }

                .pill {
                    border-radius: 20px;
                }

            </style>
@endsection