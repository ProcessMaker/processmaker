<template>
  <div class="d-flex justify-content-center">
    <b-card class="w-75">

      columsn: {{columns}}

      <div class="mb-3">
        <pmql-input ref="pmql_input" search-type="tasks" :value="'foo'" :url-pmql="'foo'" :filters-value="'foo'"
          :ai-enabled="false" :show-filters="true" :aria-label="$t('Advanced Search (PMQL)')">

          <template v-slot:right-buttons>
            <b-button class="ml-md-2" v-b-modal.columns>
              <i class="fas fw fa-cog"></i>
            </b-button>
          </template>
        </pmql-input>
      </div>

      <tasks-list ref="taskList" :filter="''" :pmql="pmql"></tasks-list>

      <b-modal
        id="columns"
        :title="$t('Columns')"
        size="lg"
        @ok="saveColumns"
      >
        <column-chooser-adapter ref="columnChooserAdapter" :pmql="pmql" />
      </b-modal>


    </b-card>
  </div>
</template>

<script>
import TasksList from "../../tasks/components/TasksList.vue";
import ColumnChooserAdapter from "./ColumnChooserAdapter.vue";
export default {
  data() {
    return {
      columns: []
    }
  },
  components: {
    TasksList,
    ColumnChooserAdapter,
  },
  methods: {
    saveColumns() {
      // this.columns = _.cloneDeep(this.$refs.columnChooserAdapter.currentColumns);

      console.log(_.cloneDeep(this.$refs.columnChooserAdapter.currentColumns));

    }
  },
  computed: {
    pmql() {
      return 'process_id = 22 AND element_id = "node_2"';
    },
  },
}
</script>