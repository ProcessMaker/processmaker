<template>
  <div class="d-flex justify-content-center">
    <b-card class="w-75">
      

          <div class="mb-3">
                    <pmql-input
                      ref="pmql_input"
                      search-type="tasks"
                      :value="'foo'"
                      :url-pmql="'foo'"
                      :filters-value="'foo'"
                      :ai-enabled="false"
                      :show-filters="true"
                      :aria-label="$t('Advanced Search (PMQL)')"
                      >

                      <template v-slot:right-buttons>
                          <b-button
                            class="ml-md-2"
                            v-b-modal.columns
                          >
                          <i class="fas fw fa-cog"></i>
                          </b-button>
                      </template>
                    </pmql-input>
              </div>

              <tasks-list
                ref="taskList"
                :filter="''"
                :pmql="pmql"
              ></tasks-list>

              <b-modal id="columns" title="BootstrapVue">
                <p class="my-4">Hello from modal!</p>
              </b-modal>

              <column-selector
                ref="columnSelector"
                :pmql="pmql"
              ></column-selector>




    </b-card>
  </div>
</template>

<script>
// resources/js/tasks/components/TasksList.vue
import TasksList from "../../tasks/components/TasksList.vue";
import ColumnSelector from "./ColumnSelector.vue";
export default {
  components: {
    TasksList,
    ColumnSelector
  },
  computed: {
    pmql() {
      return 'process_id = 22 AND element_id = "node_2"';
    },
  },
}
</script>