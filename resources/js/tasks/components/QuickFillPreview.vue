<template>
    <div>
        <div style="display: flex; justify-content: space-between; align-items: center;" class="h-100">
            <span style="flex-grow: 1;">{{ $t('Quick Fill') }}</span>
            <b-button 
                class="btn-cancel"
                @click="$emit('close')"
            >{{ $t('CANCEL') }}
            </b-button>
        </div>
        <div class="search-bar flex-grow w-100">
        <div class="d-flex align-items-center">
            <i class="fa fa-search ml-3 pmql-icons">
            </i>
            <textarea  ref="search_input"
                    type="text"
                    :aria-label="''"
                    :placeholder="$t('Search here')"
                    rows="1"
                    @keydown.enter.prevent
                    class="pmql-input">
            </textarea>
        </div>
        <div class="container">
        <tasks-list
            ref="taskList"
            :disable-tooltip="true"
            :disable-quick-fill-tooltip="true"
            :columns="columns"
            @selected="selected"
            :pmql="pmql"
            :advanced-filter-prop="filter"
        >
        <template v-slot:tooltip="{ tooltipRowData }">
          <b-button 
            class="btn-this-data"
            @click="buttonThisData(tooltipRowData)"
          >{{ $t('Use This Task Data') }}
          </b-button>
        </template>
      </tasks-list>
        </div>
    </div>
    </div>
</template>
<script>
export default {
props: ['task', 'data'],
data() {
    return {
        taskData: {},
        processID: 27,
        filter: [
            {
                "subject": { "type": "Status" },
                "operator": "=",
                "value": "Completed",
                "_column_field": "status",
                "_column_label": "Status"
            }
        ],
        pmql: '(user_id = 1 and status="Completed" and process_id='+this.task.process_id+')',
        columns:[
        {
          label: "Case #",
          field: "case_number",
          sortable: true,
          default: true,
          width: 60,
          filter_subject: { type: 'Relationship', value: 'processRequest.case_number' },
          order_column: 'process_requests.case_number',
        },
        {
          label: "Case title",
          field: "case_title",
          name: "__slot:case_number",
          sortable: true,
          default: true,
          width: 150,
          truncate: true,
          filter_subject: { type: 'Relationship', value: 'processRequest.case_title' },
          order_column: 'process_requests.case_title',
        },
        {
          label: "Due date",
          field: "due_at",
          format: "datetime",
          sortable: true,
          default: true,
          width: 160,
        },
      ],
      dataTasks: {},
    }
  },
  methods: {
    selected(taskData) {
      // console.log("HERE");
      // let selTask= `/tasks/${taskData.id}/edit/preview`
      // this.$emit("quick-fill-data", { 
      //   task: this.task, 
      //   data: this.data, 
      //   selectedTask: selTask, 
      //   taskDataSelectedId: taskData.id 
      //   });
      // ;
    },
    buttonThisData(tooltipRowData) {
      this.$emit("quick-fill-data", tooltipRowData.data);
      this.$emit("close");
    },
  }
}
</script>
<style>
.btn-cancel {
    background-color: #D8E0E9;
}
</style>
