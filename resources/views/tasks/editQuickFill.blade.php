@extends('layouts.layout')

@section('meta')
    <meta name="request-id" content="{{ $task->processRequest->id }}">
@endsection

@section('title')
    {{__('Quick fill')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_task')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Tasks') => route('tasks.index'),
        function() use ($task) {
            if ($task->advanceStatus == 'completed') {
                return ['Completed Tasks', route('tasks.index', ['status' => 'CLOSED'])];
            }
            return ['To Do Tasks', route('tasks.index')];
        },
        $task->processRequest->name =>
            Auth::user()->can('view', $task->processRequest) ? route('requests.show', ['request' => $task->processRequest->id]) : null,
            __('Quick Fill') => null,
            '@{{taskTitle}}' => null
      ], 'attributes' => 'v-cloak'])
@endsection
@section('content')
<div v-cloak id="quick" class="container-fluid px-3">
    <quick-fill-preview
    class="quick-fill-preview"
    :task="data"
    :prop-from-button ="'fullTask'"
    :prop-columns="columns"
    :prop-filters="filters"
    ></quick-fill-preview>
</div>
@endsection
@section('js')
<script>
const task = @json($task);
</script>
<script src="{{mix('js/tasks/show.js')}}"></script>
<script>
      const store = new Vuex.Store();
      const main = new Vue({
        store: store,
        el: "#quick",
        data: {
            data: {},
            //filters: " and process_id=35 ",
            filters: [
          {
            subject: { type: "Field", value: "process_id" },
            operator: "=",
            value: 24,
          },
          {
            subject: { type: "Field", value: "element_id" },
            operator: "=",
            value: "node_2"
          },
        ],
            columns: [
        {
          label: "Case #",
          field: "case_number",
          filter_subject: {
            type: "Relationship",
            value: "processRequest.case_number",
          },
          order_column: "process_requests.case_number",
        },
        {
          label: "Case title",
          field: "case_title",
          name: "__slot:case_number",
          filter_subject: {
            type: "Relationship",
            value: "processRequest.case_title",
          },
          order_column: "process_requests.case_title",
        },
        {
          label: "Process",
          field: "process",
          sortable: true,
          default: true,
          width: 140,
          truncate: true,
          filter_subject: {
            type: "Relationship",
            value: "processRequest.name",
          },
          order_column: "process_requests.name",
        },
        {
          label: "Task",
          field: "task_name",
          sortable: true,
          default: true,
          width: 140,
          truncate: true,
          filter_subject: { value: "element_name" },
          order_column: "element_name",
        },
        {
          label: "Status",
          field: "status",
          sortable: true,
          default: true,
          width: 100,
          filter_subject: { type: "Status" },
        },
        {
          label: "Due date",
          field: "due_at",
          format: "datetime",
          sortable: true,
          default: true,
          width: 140,
        }
      ],

        },
        mounted() {
            console.log("en mounted editQuickFill task: ", this.task);
            //this.fetchTasks();
        },
        methods: {
            fetchTasks() {
                let query=`tasks?page=1
    &include=process,processRequest,processRequest.user,user,data
    &pmql=
    &per_page=10
    &order_by=ID
    &order_direction=DESC
    &non_system=true
    &advanced_filter=[
        {
            "subject": {
                "type": "Status"
            },
            "operator": "=",
            "value": "In Progress",
            "_column_field": "status",
            "_column_label": "Status"
        }
    ]`;
                ProcessMaker.apiClient
              .get(query)
              .then(response => {
                console.log("Data en editQuickFill: ", response.data);
                this.data = response.data;
              });
            }
        }
      });
</script>
@endsection

