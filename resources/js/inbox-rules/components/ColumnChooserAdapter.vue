<template>
  <div class="tab-content">
    <div class="tab-content-columns">
      current columns: {{currentColumns}}
      <column-chooser ref="columnChooser" v-model="currentColumns" :available-columns="availableColumns"
        :default-columns="defaultColumns" :data-columns="dataColumns" @input="updated">
      </column-chooser>
    </div>
  </div>
</template>

<script>
import ColumnChooser from '../../components/shared/ColumnChooser.vue';
export default {
  components: {
    ColumnChooser
  },
  props: {
    pmql: {
      type: String
    },
    columns: {
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
      defaultColumns: [],
      currentColumns: []
    }
  },
  methods: {
    updated() {
      console.log("Updated");
    },
  },
  mounted() {
    let savedSearchIdRoute = '';
    if (this.savedSearchId) {
      savedSearchIdRoute = this.savedSearchId + '/';
    }
    window.ProcessMaker.apiClient
      .get("saved-searches/" + savedSearchIdRoute + "columns", {
        params: {
          pmql: this.pmql
        }
      })
      .then(response => {
        this.availableColumns = response.data.available;
        this.dataColumns = response.data.data;
        this.defaultColumns = response.data.default;
      });
  }
}
</script>

<style>
.tab-content {
  max-height: 500px;
}
.column-container {
  max-height: 500px;
}

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