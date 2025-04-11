@extends('layouts.layout')

@section('title')
    {{__('Logs')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Admin') => route('admin.index'),
        __('Logs') => null,
    ]])
@endsection
@section('content')
    <div class="px-3" id="emailLogs">
        <div class="d-flex justify-content-between align-items-center mb-4 gap-8">
            <input type="text" placeholder="{{__('Search here')}}" class="form-control">
            <a
                href="{{ route('admin-email-logs.export', ['format' => 'pdf']) }}"
                class="btn btn-primary ml-2 text-nowrap"
            >
                <i class="fa fa-download"></i>
                {{__('Export to PDF')}}
            </a>
            <a
                href="{{ route('admin-email-logs.export', ['format' => 'csv']) }}"
                class="btn btn-primary ml-2 text-nowrap"
            >
                <i class="fa fa-download"></i>
                {{__('Export to CSV')}}
            </a>
        </div>
        <!-- TODO: Replace with final component and real data -->
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>Case #</th>
                    <th>Case Title</th>
                    <th>Process</th>
                    <th>Task</th>
                    <th>Date</th>
                    <th>Subject</th>
                    <th>From</th>
                    <th>To</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // @todo: replace with final component and real data
                $emailLogs = [
                    [
                        'case_number' => '123456',
                        'case_title' => 'Case Title',
                        'process' => 'Process',
                        'task' => 'Task',
                        'date' => '2021-01-01',
                        'subject' => 'Subject',
                        'from' => 'From',
                        'to' => 'To',
                    ]
                ];
                ?>
                @foreach($emailLogs as $log)
                    <tr>
                        <td>{{ $log['case_number'] }}</td>
                        <td>{{ $log['case_title'] }}</td>
                        <td>{{ $log['process'] }}</td>
                        <td>{{ $log['task'] }}</td>
                        <td>{{ $log['date'] }}</td>
                        <td>{{ $log['subject'] }}</td>
                        <td>{{ $log['from'] }}</td>
                        <td>{{ $log['to'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="flex justify-between items-center mt-4">
            <span>1 of 10</span>
            <span>145 items</span>
            <span>15 per Page</span>
        </div>
    </div>
@endsection


@section('js')

@endsection
