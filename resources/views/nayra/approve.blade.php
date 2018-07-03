@extends('layouts.layout')

@section('content')
<div class="container" id="view">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Approve request</h5>
            <form>
                <input type="hidden" v-model="processUid" placeholder="Process UID">
                <input type="hidden" v-model="eventUid" placeholder="Event UID">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="approve" v-model="approve" id="approveYes" value="yes">
                    <label class="form-check-label" for="approveYes">
                        Yes
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="approve" v-model="approve" id="approveNo" value="no">
                    <label class="form-check-label" for="approveNo">
                        No
                    </label>
                </div>
                <button type="submit" class="btn btn-primary" @click="submit">Continue</button>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{mix('js/nayra/approve.js')}}"></script>
@endsection
