<template>
  <div>
    <b-card v-if="!savedSearchId && !task">
      <p>Select a saved search above.</p>
    </b-card>
    <b-card v-else>
      <div v-if="task">
        {{ taskTitle }}
      </div>
      <div class="mb-3">
        <pmql-input ref="pmql_input"
          :value="pmql"
          search-type="tasks"
          :ai-enabled="false"
          :show-filters="true"
          :aria-label="$t('Advanced Search (PMQL)')"
          :advanced-filter-prop="savedSearchAdvancedFilter"
          :show-search-bar="false"
          :show-pmql-badge="!!savedSearchId"
        >
          <template v-slot:right-of-badges v-if="showColumnSelectorButton">
            <b-button class="ml-md-2" v-b-modal.columns>
              <i class="fas fw fa-cog"></i>
            </b-button>
          </template>
        </pmql-input>
      </div>

      <tasks-list
        v-if="ready"
        ref="taskList"
        :pmql="pmql"
        :advanced-filter-prop="savedSearchAdvancedFilter"
        @advanced-filter-updated="savedSearchAdvancedFilter = $event"
        :saved-search="savedSearch?.id"
        :columns="columns"
        @submit=""
        @count="$emit('count', $event)"
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

const defaultFilter = {
  filters: [],
  order: { by: 'id', dir: 'desc' },
};
export default {
  props: {
    savedSearchId: {
      type: Number,
      default: null
    },
    taskId: {
      type: Number,
      default: null
    },
    showColumnSelectorButton: {
      type: Boolean,
      default: true
    }
  },
  data() {
    return {
      savedSearch: null,
      columns: [],
      defaultColumns: [],
      savedSearchAdvancedFilter: defaultFilter,
      ready: false,
      task: null,
    }
  },
  components: {
    TasksList,
    ColumnChooserAdapter,
  },
  methods: {
    emitSavedSearchData() {
      this.$emit('saved-search-data', {
        pmql: this.pmql,
        advanced_filter: this.savedSearchAdvancedFilter,
        columns: this.columns
      });
    },
    applyColumns() {
      this.columns = this.$refs.columnChooserAdapter.currentColumns;
      this.savedSearchAdvancedFilter = _.cloneDeep(defaultFilter);
    },
    loadSavedSearch() {
      this.ready = false;
      return window.ProcessMaker.apiClient
        .get("saved-searches/" + this.savedSearchId)
        .then(response => {
          this.savedSearch = response.data;
          this.columns = this.defaultColumns = response.data._adjusted_columns?.filter(c => c.field !== 'is_priority');
          this.savedSearchAdvancedFilter = response.data.advanced_filter;
          this.ready = true;
        });
    },
    loadTask() {
      this.ready = false;

      this.columns = this.defaultColumns =
        _.get(window, 'Processmaker.defaultColumns', [])
        .filter(c => c.field !== 'is_priority');

      return window.ProcessMaker.apiClient
        .get("tasks/" + this.taskId)
        .then(response => {
          this.task = response.data;
          this.ready = true;
        });
    },
    showColumns() {
      this.$bvModal.show("columns");
    }
  },
  watch: {
    task: {
      deep: true,
      handler() {
      }
    },
    savedSearchAdvancedFilter: {
      deep: true,
      handler() {
        this.emitSavedSearchData();
      }
    },
    pmql: {
      handler() {
        this.emitSavedSearchData();
      }
    },
    ready: {
      handler() {
        this.emitSavedSearchData();
      }
    },
    taskId: {
      handler() {
        this.loadTask();
      }
    },
    savedSearchId: {
      handler() {
        this.loadSavedSearch();
      }
    }
  },
  computed: {
    pmql() {

      const pmqls = [];

      if (this.task) {
        pmqls.push('(user_id = ' + window.ProcessMaker.user.id + ' AND process_id = ' + this.task.process_id + ' AND element_id = "' + this.task.element_id + '")');
      }

      if (this.savedSearch?.pmql) {
        pmqls.push(this.savedSearch.pmql);
      }

      return pmqls.join(' AND ');
    },
    taskTitle() {
      if (this.task) {
        return this.$t('Your {{title}} tasks', {title: this.task.element_name})
      }
      return '';
    }
  },
  mounted() {
    if (this.savedSearchId) {
      this.loadSavedSearch();
    } else if (this.taskId) {
      this.loadTask();
    }
  }
}
</script>