<template>
    <div v-show="isQuick">
        <!-- <div style="display: inline-block;">
            <span>Quick Fill</span>
            <b-button variant="light"
            >
            {{ $t('CANCEL') }})
            </b-button>
        </div> -->
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <span style="flex-grow: 1;">{{ $t('Quick Fill') }}</span>
            <b-button class="btn-cancel">{{ $t('CANCEL') }}</b-button>
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
        <!-- <tasks-list
            ref="taskList"
            :filter="filter"
            :pmql="fullPmql"
            :columns="columns"
            :disableTooltip="true"
        ></tasks-list> -->
        <tasks-list
            ref="taskList"
            :disable-tooltip="true"
        ></tasks-list>
        <!-- <filter-table
        :headers="tableHeadersTasks"
        :data="dataTasks"
        table-name="task-tab"
        @table-row-click="handleRowClick"
      />
      <pagination-table
        :meta="dataTasks.meta"
        @page-change="changePageTasks"
      /> -->
    </div>
    </div>
</template>
<script>

//import { FilterTable } from "../../components/shared";
import paginationTable from "../../components/shared/PaginationTable.vue";
export default {
components: { paginationTable },
props: ['isQuick'],
data() {
    return {
        filter: {},
        //columns: {},
        pmql: {},
        fullPmql: {},
        columns:[
        {
          label: "Case #",
          field: "case_number",
          sortable: true,
          default: true,
          width: 80,
          filter_subject: { type: 'Relationship', value: 'processRequest.case_number' },
          order_column: 'process_requests.case_number',
        },
        {
          label: "Case title",
          field: "case_title",
          name: "__slot:case_number",
          sortable: true,
          default: true,
          width: 220,
          truncate: true,
          filter_subject: { type: 'Relationship', value: 'processRequest.case_title' },
          order_column: 'process_requests.case_title',
        },
        {
          label: "Priority",
          field: "is_priority",
          sortable: false,
          default: true,
          width: 40,
        },
        {
          label: "Process",
          field: "process",
          sortable: true,
          default: true,
          width: 140,
          truncate: true,
          filter_subject: { type: 'Relationship', value: 'processRequest.name' },
          order_column: 'process_requests.name',
        },
        {
          label: "Task",
          field: "task_name",
          sortable: true,
          default: true,
          width: 140,
          truncate: true,
          filter_subject: { value: 'element_name' },
          order_column: 'element_name',
        },
        // {
        //   label: "Status",
        //   field: "status",
        //   sortable: true,
        //   default: true,
        //   width: 100,
        //   filter_subject: { type: 'Status' },
        // },
        {
          label: "Due date",
          field: "due_at",
          format: "datetime",
          sortable: true,
          default: true,
          width: 140,
        },
        {
          label: "Draft",
          field: "draft",
          sortable: false,
          default: true,
          hidden: true,
          width: 40,
        },
      ],
      dataTasks: {},
    }
  },
  mounted() {
    //require('../index.js');
    const isStatusCompletedList = window.location.search.includes("status=CLOSED");
  }
}
</script>
<style>
.btn-cancel {
    background-color: #D8E0E9;
}
</style>
