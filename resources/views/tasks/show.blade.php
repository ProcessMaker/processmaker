@extends('layouts.layout')

@section('content')
<div>
    <nav>
        <ul class="nav row" style="height:40px; background-color:#b6bfc6;">
        <li class="nav-item col-3 align-self-center">
            <a class="nav-link active text-light p-0 ml-3" href="#"><i class="fas fa-long-arrow-alt-left fa-lg mr-2"></i>BACK TO REQUEST DETAILS</a>
        </li>
        <li class="nav-item col-3 align-self-center text-right">
            <a class="nav-link text-light p-0" href="#"><h4 class="m-0">Task: Approve Leave of Absence</h4></a>
        </li>
        <li class="nav-item col align-self-center">
            <a class="nav-link p-0" href="#" ><span class="pill badge-light p-1 pl-2 pr-2" style="border-radius: 20px;"><i class="fas fa-circle text-primary mr-2"></i><span>Pending</span></a>
        </li>
        </ul>
    </nav>
    <div class="d-flex container mt-3">
        <div class="col-9 mt-5 mb-5">
            <div class="container mb-5 p-5">
                <h3 class="font-weight-bold">Request</h3>
                <form>
                    <div class="form-group">
                        <label for="groupNameStart">Start Date</label>
                        <input type="text" class="form-control" id="groupNameStart">
                    </div>
                    <div class="form-group">
                        <label for="groupNameEnd">End Date</label>
                        <input type="text" class="form-control" id="groupNameEnd">
                    </div>
                    <div class="form-group mb-3">
                        <label for="description">Reason</label>
                        <textarea class="form-control" id="description" rows="3"></textarea>
                    </div>
                    <h3 class="font-weight-bold mb-3">Approve?</h3>
                    <div class="mb-2">
                        <input type="checkbox" aria-label="Checkbox for following text input">
                        <span class="text-secondary"> Yes</span>
                    </div>
                    <div class="form-group">
                        <button class="btn btn-primary" type="submit">Continue</button>
                    </div>
                </form>
            </div>
            <br>
            <br>
            <div>
                <div class="row">
                    <div class="col">
                        <div class="card card-body border-0">
                            <div align="center" class="text-muted">
                            You have not posted any comments yet.
                            </div>
                            <div class="row mt-3">
                                <div class="col-1">
                                    <img class="mr-2" src="{{ asset('img/avatar-placeholder.gif') }}" style="height: 45px; border-radius: 50%;"/>
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
            <div class="card card-body border-0">
                <div class="row">
                    <h4 class="text-primary col-9">Task Details</h4>
                    <i class="fas fa-caret-square-up text-secondary col"></i>
                </div>
                <div class="text-secondary">Assigned to:</div><div class="mt-1">Name Name</div>
                <div class="text-secondary mt-2">Assigned:</div><div class="mt-1">01/01/1001 11:11</div>

                <div class="text-secondary mt-5">Date Created:</div><div class="mt-1">3 days ago</div>
                <div class="text-secondary mt-2">Due Date:</div><div class="mt-1">10 mins ago</div>
                <div class="text-secondary mt-2">Last Modified:</div><div class="mt-1 mb-4">24hrs</div>
            </div>
            <br>
            <div class="card card-body border-0">
                <div class="row">
                    <h4 class="text-primary col-9">Request Details</h4>
                    <i class="fas fa-caret-square-up text-secondary col"></i>
                </div>
                <div class="text-secondary">Process:</div><div class="mt-1">Name Name</div>
                <div class="text-secondary mt-2">Created by:</div><div class="mt-1">01/01/1001 11:11</div>
                <br>
                <div class="text-secondary ">Date Created:</div><div class="mt-1">3 days ago</div>
                <div class="text-secondary mt-2">Created:</div><div class="mt-1">10 mins ago</div>
                <div class="text-secondary mt-2">Last Modified:</div><div class="mt-1">24hrs</div>
                <div class="text-secondary mt-2">Duration:</div><div class="mt-1 mb-4">24hrs</div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{mix('js/tasks/show.js')}}"></script>
@endsection
