<template>
  <div>
    <b-card v-if="!savedSearchId && (!processId || !elementId)">
      <p>No saved search or task parameters provided</p>
    </b-card>
    <b-card v-else>
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
          <template v-slot:right-of-badges>
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
      savedSearchAdvancedFilter: defaultFilter,
      ready: false,
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
      });
    },
    applyColumns() {
      this.columns = this.$refs.columnChooserAdapter.currentColumns;
      this.savedSearchAdvancedFilter = _.cloneDeep(defaultFilter);
    },
    loadSavedSearch() {
      return window.ProcessMaker.apiClient
        .get("saved-searches/" + this.savedSearchId)
        .then(response => {
          this.savedSearch = response.data;
          this.columns = this.defaultColumns = response.data._adjusted_columns.filter(c => c.field !== 'is_priority');
          this.savedSearchAdvancedFilter = response.data.advanced_filter
        });
    }
  },
  watch: {
    savedSearchAdvancedFilter: {
      deep: true,
      handler() {
        this.emitSavedSearchData();
      }
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
  },
  mounted() {
    if (this.savedSearchId) {
      
      this.loadSavedSearch().then(() => {
         this.ready = true
      });

    } else if (this.processId && this.elementId) {

      this.columns = this.defaultColumns =
        _.get(window, 'Processmaker.defaultColumns', [])
        .filter(c => c.field !== 'is_priority');

      this.ready = true;
    }
  }
}
</script>