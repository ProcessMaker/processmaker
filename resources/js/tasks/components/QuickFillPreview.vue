<template>
    <div v-show="showQuickFillPreview">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <span style="flex-grow: 1;">{{ $t('Quick Fill') }}</span>
            <b-button 
                class="btn-cancel"
                @click="cancelQuickFill()"
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
        ></tasks-list>
        </div>
    </div>
    </div>
</template>
<script>
export default {
props: ['showQuickFillPreview', 'task', 'data'],
data() {
    return {
        filter: [
            {
                "subject": { "type": "Status" },
                "operator": "=",
                "value": "Completed",
                "_column_field": "status",
                "_column_label": "Status"
            }
        ],
        pmql: '(user_id = 1)',
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
    cancelQuickFill() {
      //here logic for cancel buton
    },
    selected(taskData) {
      //this.$root.$emit("selectedTaskForQuickFill", { task: this.task, data: this.data });
      this.$root.$emit("selectedTaskForQuickFill", { task: taskData, data: this.data });
    }
  }
}
</script>
<style>
.btn-cancel {
    background-color: #D8E0E9;
}
</style>
