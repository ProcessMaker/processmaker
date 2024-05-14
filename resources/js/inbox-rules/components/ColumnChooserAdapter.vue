<template>
  <div>
    <column-chooser v-model="currentColumns" 
                    :available-columns="availableColumns"
                    :default-columns="defaultColumns" 
                    :data-columns="dataColumns">
    </column-chooser>
  </div>
</template>

<script>
import ColumnChooser from "../../components/shared/ColumnChooser.vue";
import cloneDeep from "lodash/cloneDeep";

export default {
  components: {
    ColumnChooser
  },
  props: {
    pmql: {
      type: String
    },
    advancedFilter: {
      type: Object,
      default: null
    },
    columns: {
      type: Array,
      default() {
        return [];
      }
    },
    defaultColumns: {
      type: Array,
      default() {
        return [];
      }
    }
  },
  data() {
    return {
      availableColumns: [],
      dataColumns: [],
      currentColumns: []
    };
  },
  computed: {
    modifiedCurrentColumns() {
      return this.currentColumns.map(column => {
        const existingColumnDefinition = this.columns.find(c => c.field === column.field);
        if (existingColumnDefinition) {
          return existingColumnDefinition;
        }

        return {
          ...column,
          filter_subject: {type: "Field", value: column.field},
          order_column: column.field
        };
      });
    }
  },
  watch: {
    columns() {
      this.currentColumns = cloneDeep(this.columns);
    }
  },
  mounted() {
    this.currentColumns = cloneDeep(this.columns);
    this.test = cloneDeep(this.columns);

    let savedSearchIdRoute = '';
    if (this.savedSearchId) {
      savedSearchIdRoute = this.savedSearchId + '/';
    }
    let url = "saved-searches/" + savedSearchIdRoute + "columns?include=available,data";
    let parameters = {
      params: {
        pmql: this.pmql,
        advanced_filter: {
          ...this.advancedFilter,
          filters: this.advancedFilter?.filters.filter(
                  f => !(f.subject.type === "Status" && f.value === "In Progress")
          )
        }
      }
    };
    ProcessMaker.apiClient.get(url, parameters)
            .then(response => {
              this.availableColumns = response.data.available.filter(this.filterAvailable);
              this.dataColumns = response.data.data;
            });
  },
  methods: {
    filterAvailable(column) {
      return !this.currentColumns.find(c => c.field === column.field);
    }
  }
}
</script>

<style>
  .column-container {
    height: 500px;
  }
  /* Copied from Collections package */
  .bg-muted {
    background-color: #fafafa;
  }
  .draggable-list {
    height: 100%;
    max-height: 100%;
    overflow-y: scroll;
  }
  .custom_icon {
    max-width: 24px;
    max-height: 24px;
    min-width: 24px;
    min-height: 24px;
  }
  .handle {
    cursor: grab;
    opacity: .5;
  }
  .column-card {
    cursor: grab;
  }
  .column-card.sortable-chosen {
    cursor: grabbing;
  }
  .column-card.sortable-ghost {
    cursor: grabbing;
  }
  .column-button {
    cursor: pointer;
  }
  .column-add {
    cursor: pointer;
  }
  .draggable-available .column-button {
    display: none;
  }
</style>