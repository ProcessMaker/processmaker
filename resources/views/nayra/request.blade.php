@extends('layouts.layout')

@section('content')
<div class="container" id="start">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Request</h5>
            <form>
                <input type="hidden" v-model="processUid" placeholder="Process UID">
                <input type="hidden" v-model="eventUid" placeholder="Event UID">
                <div class="form-group">
                    <label for="startDate">Start date</label>
                    <input id ="startDate" aria-describedby="startDateHelp" type="datetime-local" class="form-control" v-model="start" placeholder="Start Date">
                </div>
                <div class="form-group">
                    <label for="endDate">End date</label>
                    <input id ="endDate" aria-describedby="endDateHelp" type="datetime-local" class="form-control" v-model="end" placeholder="End Date">
                </div>
                <div class="form-group">
                    <label for="endDate">Reason</label>
                    <textarea id ="reason" class="form-control" v-model="reason" placeholder="Reason" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary" @click="submit">Continue</button>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{mix('js/nayra/request.js')}}"></script>
@endsection
