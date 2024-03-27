<template>
  <div>
    <div v-if="!savedSearchId && !task">
      <PMMessageScreen>
        <template v-slot:content>
          <img src="/img/funnel-fill-elements-blue.svg" 
               :alt="$t('Select a saved search above.')"/>
          <b>{{ $t('Select the Load a saved search control above.') }}</b>
          <span v-html="$t('Select the <b>Load a saved search</b> control above.')">
          </span>
        </template>
      </PMMessageScreen>
    </div>
    <div v-else>
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
          :advanced-filter="savedSearchAdvancedFilter"
          :columns="columns"
          :default-columns="defaultColumns"
          />
      </b-modal>
    </div>
  </div>
</template>

<script>
  import TasksList from "../../tasks/components/TasksList.vue";
  import ColumnChooserAdapter from "./ColumnChooserAdapter.vue";
  import PMMessageScreen from "../../components/PMMessageScreen.vue";
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
      },
      isNew: {
        type: Boolean,
        default: false
      }
    },
    data() {
      return {
        savedSearch: null,
        columns: [],
        defaultColumns: [],
        savedSearchAdvancedFilter: null,
        originalSavedSearchAdvancedFilter: null,
        ready: false,
        task: null
      };
    },
    components: {
      TasksList,
      ColumnChooserAdapter,
      PMMessageScreen
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
        this.columns = this.$refs.columnChooserAdapter.modifiedCurrentColumns;
        this.resetFilters();
      },
      resetFilters() {
        this.savedSearchAdvancedFilter = _.cloneDeep(this.originalSavedSearchAdvancedFilter);
      },
      defaultTaskFilters() {
        return {
          order: {by: 'id', direction: 'desc'},
          filters: [
            this.userIdFilter(),
            {
              subject: {
                type: "Field",
                value: 'process_id'
              },
              operator: "=",
              value: this.task.process_id,
              _column_label: "Process ID"
            },
            {
              subject: {
                type: "Field",
                value: 'element_id'
              },
              operator: "=",
              value: this.task.element_id,
              _column_label: "Node ID"
            },
            {
              subject: {
                type: "Status"
              },
              operator: "=",
              value: 'In Progress',
              _column_label: "Status"
            }
          ]
        };
      },
      addRequiredSavedSearchFilters(filter) {
        if (filter.filters) {
          const hasUserIdFilter = filter.filters.some(f => {
            return f.subject.type === 'Field' && f.subject.value === 'user_id';
          });

          if (!hasUserIdFilter) {
            filter.filters.push(this.userIdFilter());
          }

          const hasStatusFilter = filter.filters.some(f => {
            return f.subject.type === 'Status' && f.value === 'In Progress';
          });

          if (!hasStatusFilter) {
            filter.filters.push(this.statusFilter());
          }

        } else {
          filter.filters = [this.userIdFilter()];
        }
        return filter;
      },
      userIdFilter() {
        return {
          subject: {
            type: "Field",
            value: 'user_id'
          },
          operator: "=",
          value: window.ProcessMaker.user.id,
          _column_label: "User ID"
        };
      },
      statusFilter() {
        return {
          subject: {
            type: "Status"
          },
          operator: "=",
          value: 'In Progress',
          _column_label: "Status"
        };
      },
      loadSavedSearch() {
        this.ready = false;
        return ProcessMaker.apiClient.get("saved-searches/" + this.savedSearchId)
                .then(response => {
                  this.savedSearch = response.data;
                  this.columns = this.defaultColumns = response.data._adjusted_columns?.filter(c => c.field !== 'is_priority');
                  this.savedSearchAdvancedFilter = response.data.advanced_filter;
                  this.originalSavedSearchAdvancedFilter = _.cloneDeep(this.savedSearchAdvancedFilter);
                  this.ready = true;
                });
      },
      // Only used when creating inbox rules.
      // Existing inbox rules always have a saved search.
      loadTask() {
        this.ready = false;

        this.columns = this.defaultColumns =
                _.get(window, 'Processmaker.defaultColumns', [])
                .filter(c => c.field !== 'is_priority');

        return ProcessMaker.apiClient.get("tasks/" + this.taskId)
                .then(response => {
                  this.task = response.data;
                  this.savedSearchAdvancedFilter = this.defaultTaskFilters();
                  this.originalSavedSearchAdvancedFilter = _.cloneDeep(this.savedSearchAdvancedFilter);
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
          this.savedSearchAdvancedFilter = this.addRequiredSavedSearchFilters(this.savedSearchAdvancedFilter);
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
          this.resetFilters();
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
        if (this.savedSearch?.pmql) {
          return this.savedSearch.pmql;
        }
        return '';
      },
      taskTitle() {
        if (this.task) {
          return this.$t('Your In-Progress {{title}} tasks', {title: this.task.element_name});
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

<style scoped>
</style>