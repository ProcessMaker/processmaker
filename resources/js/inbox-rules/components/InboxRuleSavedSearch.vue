<template>
  <div class="d-flex justify-content-center">
    <b-card class="w-75">

      <div class="mb-3">
        <pmql-input ref="pmql_input"
          search-type="tasks"
          :value="'foo'"
          v-model="pmql"
          :filters-value="'foo'"
          :ai-enabled="false"
          :show-filters="true"
          :aria-label="$t('Advanced Search (PMQL)')"
          :input-advanced-filter="savedSearchAdvancedFilter"
        >
          <template v-slot:right-buttons>
            <b-button class="ml-md-2" v-b-modal.columns>
              <i class="fas fw fa-cog"></i>
            </b-button>
          </template>
        </pmql-input>
      </div>

      <tasks-list
        v-if="savedSearch"
        ref="taskList"
        :pmql="pmql"
        :advanced-filter-prop="savedSearchAdvancedFilter"
        :saved-search="savedSearch.id"
        :columns="columns"
        @submit=""
      >
      </tasks-list>

      <b-modal
        id="columns"
        :title="$t('Columns')"
        size="lg"
        @ok="applyColumns"
      >
        <column-chooser-adapter
          ref="columnChooserAdapter"
          :pmql="pmql"
          :columns="columns"
          :default-columns="defaultColumns"
      />
      </b-modal>


    </b-card>
  </div>
</template>

<script>
import TasksList from "../../tasks/components/TasksList.vue";
import ColumnChooserAdapter from "./ColumnChooserAdapter.vue";
export default {
  props: {
    savedSearchId: {
      type: Number,
      required: true
    },
    processId: {
      type: Number,
      default: null
    },
    elementId: {
      type: String,
      default: null
    }
  },
  data() {
    return {
      savedSearch: null,
      columns: [],
      defaultColumns: [],
    }
  },
  components: {
    TasksList,
    ColumnChooserAdapter,
  },
  methods: {
    applyColumns() {
      this.columns = this.$refs.columnChooserAdapter.currentColumns;
    },
    loadSavedSearch() {
      window.ProcessMaker.apiClient
        .get("saved-searches/" + this.savedSearchId)
        .then(response => {
          this.savedSearch = response.data;
          this.columns = this.defaultColumns = response.data._adjusted_columns.filter(c => c.field !== 'is_priority');
        });
    }
  },
  computed: {
    pmql() {

      const pmqls = [];

      if (this.savedSearch?.key === 'tasks') {
        // The default saved search does not have the user filter in the pmql
        pmqls.push("(user_id = " + window.ProcessMaker.user.id + ")");
      }

      if (this.processId && this.elementId) {
        pmqls.push('(process_id = ' + this.processId + ' AND element_id = "' + this.elementId + '")');
      }

      if (this.savedSearch?.pmql) {
        pmqls.push(this.savedSearch.pmql);
      }

      return pmqls.join(' AND ');
    },
    savedSearchAdvancedFilter() {
      return this.savedSearch?.advanced_filter?.filters;
    }
  },
  mounted() {
    this.loadSavedSearch();
  }
}
</script>