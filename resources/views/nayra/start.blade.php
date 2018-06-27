@extends('layouts.layout')

@section('content')
<div class="container" id="start">
    <div class="row">
        <div class="col-sm">

            <h1>Nayra Test Start Form</h1>

            <input v-model="processUid" placeholder="Process UID">
            <input v-model="eventUid" placeholder="Event UID">
            <input v-model="start" placeholder="Start Date">
            <input v-model="end" placeholder="End Date">
            <textarea v-model="reason" rows="5"></textarea>
            <button @click="submit">Continue</button>

        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{mix('js/nayra/start.js')}}"></script>
@endsection