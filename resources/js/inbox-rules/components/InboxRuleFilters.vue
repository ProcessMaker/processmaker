<template>
  <div>
    <div v-if="!savedSearchId && !task" >
      <PMMessageScreen>
        <template v-slot:content>
          <img src="/img/funnel-fill-elements-blue.svg" 
               :alt="$t('Select a saved search above.')"/>
          <b class="no-rule-class-title">{{ $t('Select a saved search above.') }}</b>
          <span class="no-rule-class-text">
            {{ $t('Choose a saved search to see the tasks that you can use with an Inbox Rule.') }}
          </span>
        </template>
      </PMMessageScreen>
    </div>
    <div v-else>
      <div v-if="filterTitle">
        {{ filterTitle }}
      </div>
      <div class="mb-3">
        <PMBadgesFilters :value="pmql"
                         :advancedFilterProp="savedSearchAdvancedFilter"
                         :task="task"
                         >
          <template v-slot:right-of-badges v-if="showColumnSelectorButton">
            <b-button class="ml-md-2" v-b-modal.columns>
              <i class="fas fw fa-cog"></i>
            </b-button>
          </template>
        </PMBadgesFilters>
      </div>

      <tasks-list
        v-if="ready"
        ref="taskList"
        :pmql="pmql"
        :disable-row-click="true"
        :disable-rule-tooltip="true"
        :advanced-filter-prop="savedSearchAdvancedFilter"
        @advanced-filter-updated="advancedFilterUpdatedFromTasksList"
        :saved-search="savedSearch?.id"
        :columns="columns"
        @submit=""
        @count="$emit('count', $event)"
        @onRendered="onTaskListRendered"
        >
        <template v-slot:no-results>
          <PMMessageScreen>
            <template v-slot:content>
              <img src="/img/funnel-fill-elements-blue.svg" 
                   :alt="$t('Select a saved search above.')"/>
              <b class="no-rule-class-title">{{ $t('No tasks to show.') }}</b>
              <span class="no-rule-class-text">
                {{ $t("But that's OK. You can still create this this Inbox Rule to apply to future tasks that match the above filters.") }}
              </span>
            </template>
          </PMMessageScreen>
        </template>
      </tasks-list>

      <b-modal
        id="columns"
        :title="$t('Columns')"
        size="lg"
        v-model="modalColumnChooserAdapter">
        <column-chooser-adapter
          ref="columnChooserAdapter"
          :pmql="pmql"
          :advanced-filter="savedSearchAdvancedFilter"
          :columns="columns"
          :default-columns="defaultColumns"
          />
        <template v-slot:modal-footer>
          <b-button @click="applyColumns" variant="primary">{{$t('Ok')}}</b-button>
        </template>
      </b-modal>
    </div>
  </div>
</template>

<script>
  import TasksList from "../../tasks/components/TasksList.vue";
  import ColumnChooserAdapter from "./ColumnChooserAdapter.vue";
  import PMMessageScreen from "../../components/PMMessageScreen.vue";
  import PMBadgesFilters from "../../components/PMBadgesFilters.vue";
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
      propSavedSearchData: {
        type: Object,
        default: () => {
          return {};
        }
      }
    },
    data() {
      return {
        savedSearch: null,
        defaultColumns: [],
        columns: [],
        savedSearchAdvancedFilter: null,
        originalSavedSearchAdvancedFilter: null,
        ready: false,
        task: null,
        modalColumnChooserAdapter: false
      };
    },
    components: {
      TasksList,
      ColumnChooserAdapter,
      PMMessageScreen,
      PMBadgesFilters
    },
    methods: {
      columnFilter(column) {
        return column.field !== 'is_priority' &&
          column.field !== 'status' &&
          column.hidden !== true;
      },
      advancedFilterUpdatedFromTasksList(filters) {
        this.savedSearchAdvancedFilter = filters;
      },
      applyColumns() {
        if (this.$refs.columnChooserAdapter.modifiedCurrentColumns.length <= 0) {
          ProcessMaker.alert(this.$t("Select at least one column."), "danger");
          return;
        }
        this.columns = this.$refs.columnChooserAdapter.modifiedCurrentColumns;
        this.modalColumnChooserAdapter = false;

        // We need to re-fetch because the the task list only includes
        // data specified in the request. If we added a column, we need
        // to request that field.
        this.$refs.taskList.fetch();
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
              //to do: PMColumnFilterPopoverCommonMixin.js will rebuild _column_field and _column_label 
              //based on the table's columns. Since the table doesn't have these columns, it will 
              //always take this value.
              _column_field: "process_id",
              _column_label: "process_id"
            },
            {
              subject: {
                type: "Field",
                value: 'element_id'
              },
              operator: "=",
              value: this.task.element_id,
              //to do: PMColumnFilterPopoverCommonMixin.js will rebuild _column_field and _column_label 
              //based on the table's columns. Since the table doesn't have these columns, it will 
              //always take this value.
              _column_field: "element_id",
              _column_label: "element_id"
            },
            this.statusFilter()
          ]
        };
      },
      defaultSavedSearchFilters() {
        return {
          order: {by: 'id', direction: 'desc'},
          filters: [
            this.statusFilter()
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
          //to do: PMColumnFilterPopoverCommonMixin.js will rebuild _column_field and _column_label 
          //based on the table's columns. Since the table doesn't have these columns, it will 
          //always take this value.
          _column_field: "user_id",
          _column_label: "user_id"
        };
      },
      statusFilter() {
        return {
          subject: {
            type: "Status"
          },
          operator: "=",
          value: 'In Progress',
          _column_field: "status",
          _column_label: "Status"
        };
      },
      loadSavedSearch() {
        this.ready = false;
        return ProcessMaker.apiClient.get("saved-searches/" + this.savedSearchId)
                .then(response => {
                  this.savedSearch = response.data;

                  if (this.columns.length === 0) {
                    const cols = response.data._adjusted_columns?.filter(this.columnFilter);
                    this.columns = _.cloneDeep(cols);
                    this.defaultColumns = _.cloneDeep(cols);
                  }

                  if (this.savedSearchAdvancedFilter === null) {
                    const advancedFilter = response.data.advanced_filter ?? this.defaultSavedSearchFilters();
                    this.savedSearchAdvancedFilter = this.addRequiredSavedSearchFilters(advancedFilter);
                    this.originalSavedSearchAdvancedFilter = _.cloneDeep(this.savedSearchAdvancedFilter);
                  }

                  this.ready = true;
                });
      },
      // Only used when creating inbox rules.
      // Existing inbox rules always have a saved search.
      loadTask() {
        this.ready = false;

        return ProcessMaker.apiClient.get("tasks/" + this.taskId, { params: {include: 'process'} })
                .then(response => {
                  this.task = response.data;

                  if (this.savedSearchAdvancedFilter === null) {
                    this.savedSearchAdvancedFilter = this.defaultTaskFilters();
                    this.originalSavedSearchAdvancedFilter = _.cloneDeep(this.savedSearchAdvancedFilter);
                  }

                  this.ready = true;
                });
      },
      showColumns() {
        this.$bvModal.show("columns");
      },
      onTaskListRendered() {
        if (this.columns.length <= 0 && this.defaultColumns.length <= 0) {
          let defaultColumns = this.$refs.taskList.tableHeaders;
          this.columns = this.defaultColumns = defaultColumns?.filter(this.columnFilter);
        }
      }
    },
    watch: {
      savedSearchData() {
        this.$emit('saved-search-data', this.savedSearchData);
      },
      task: {
        deep: true,
        handler() {
        }
      },
      propSavedSearchData: {
        handler() {
          this.columns = this.propSavedSearchData?.columns ?? [];
          this.savedSearchAdvancedFilter = this.propSavedSearchData?.advanced_filter ?? null;
        },
        deep: true,
        immediate: true
      },
      taskId: {
        handler() {
          if (!this.savedSearchId) {
            this.loadTask();
          }
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
      filterTitle() {
        if (this.task) {
          return this.$t('Your In-Progress "{{title}}" tasks', {title: this.task.element_name});
        } else if (this.savedSearch?.title) {
          return this.$t('Your In-Progress tasks in the saved search "{{title}}"', {title: this.savedSearch.title});
        }
        return null;
      },
      savedSearchData() {
        return {
          pmql: this.pmql,
          advanced_filter: this.savedSearchAdvancedFilter,
          columns: this.columns
        };
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
  .no-rule-class-title {
    color: #556271;
    font-size: 24px;
  }

  .no-rule-class-text {
    color: #556271;
    font-size: 16px;
  }
</style>
