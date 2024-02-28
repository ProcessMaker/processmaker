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
        <tasks-list
            ref="taskList"
            :filter="filter"
            :pmql="fullPmql"
            :columns="columns"
            :disableTooltip="true"
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
import TasksList from "./TasksList.vue";
export default {
//components: { paginationTable, TasksList },
components: { paginationTable, TasksList},
props: ['isQuick'],
data() {
    return {
        filter: {},
        columns: {},
        pmql: {},
        tableHeadersTasks: [
        {
          label: "CASE #",
          field: "case_number",
          sortable: true,
          default: true,
          width: 55,
        },
        {
          label: "CASE TITLE",
          field: "case_title",
          sortable: true,
          default: true,
          width: 140,
          truncate: true,
        },
        {
          label: "Priority",
          field: "is_priority",
          sortable: false,
          default: true,
          width: 40,
        },
        {
          label: "TASK NAME",
          field: "element_name",
          sortable: true,
          default: true,
          width: 140,
          truncate: true,
        },
        {
          label: "STATUS",
          field: "status",
          sortable: true,
          default: true,
          width: 100,
        },
        {
          label: "DUE DATE",
          field: "due_at",
          format: "datetime",
          sortable: true,
          default: true,
          width: 140,
        },
      ],
      dataTasks: {},
    }
  },
  mounted() {
    //require('../index.js');
  }
}
</script>
<style>
.btn-cancel {
    background-color: #D8E0E9;
}
</style>
