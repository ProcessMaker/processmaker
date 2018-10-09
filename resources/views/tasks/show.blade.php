@extends('layouts.layout')

@section('content')
<div class="nav row taskNav">
    <div class="col-3 align-self-center">
        <a class="nav-link active text-light p-0 ml-3" href="#"><i class="fas fa-long-arrow-alt-left fa-lg mr-2"></i>BACK TO REQUEST DETAILS</a>
    </div>
    <div class="col-3 align-self-center text-right">
        <a class="nav-link text-light p-0" href="#"><h4 class="m-0">Task: Approve Leave of Absence</h4></a>
    </div>
    <div class="col align-self-center">
        <a class="nav-link p-0" href="#" ><span class="pill badge-light p-1 pl-2 pr-2"><i class="fas fa-circle text-primary mr-2"></i><span>Pending</span></a>
    </div>
</div>
<br>
<div class="d-flex container">
    <br>
    <div class="col-9">
        <br>
        <div class="container p-5">
            <h3>Request</h3>
            <div id="request">
            </div>
        </div>

    </div> 
    
    <div class="col-3">
        <div class="card card-body border-0">
            <div class="row">
                <h4 class="text-primary col-9">Task Details</h4>
                <i class="fas fa-caret-square-up text-secondary col"></i>
            </div>
            <div class="text-secondary">Assigned to:</div>
            <p>Name Name</p>
            <span class="text-secondary">Assigned:</span>
            <p>01/01/1001 11:11</p>
            <div class="text-secondary mt-5">Date Created:</div>
            <p>3 days ago</p>
            <div class="text-secondary ">Due Date:</div>
            <p>10 mins ago</dipv>
            <div class="text-secondary">Last Modified:</div>
            <p class="mb-4">24hrs</p>
        </div>
        <br>
        <div class="card card-body border-0">
            <div class="row">
                <h4 class="text-primary col-9">Request Details</h4>
                <i class="fas fa-caret-square-up text-secondary col"></i>
            </div>
            <div class="text-secondary">Process:</div><p>Name Name</p>
            <div class="text-secondary">Created by:</div><p>01/01/1001 11:11</p>
            <div class="text-secondary ">Date Created:</div><p>3 days ago</p>
            <div class="text-secondary">Created:</div><p>10 mins ago</p>
            <div class="text-secondary">Last Modified:</div><p>24hrs</p>
            <div class="text-secondary">Duration:</div><p class="mb-4">24hrs</p>
        </div>
    </div>
</div>
<br>
<br>
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
