<template>
  <div>
    <div class="form-group">
      <label>Name</label>
      <input v-model="definition.name" name="name" placeholder type="text" class="form-control" />
      <small class="form-text text-muted">The name of the data source service</small>
    </div>
    <div class="form-group">
      <label>Data Source</label>
      <select-from-api
        ref="dataSource"
        v-model="config.dataSource"
        api="datasources"
        :placeholder="$t('Select a datasource')"
      >
        <template slot="noResult">{{ $t('No data sources found') }}</template>
      </select-from-api>
      <small class="form-text text-muted">Select the data source</small>
    </div>
    <div class="form-group">
      <label>Endpoint</label>
      <multiselect
        v-model="config.endpoint"
        :placeholder="$t('Select an endpoint')"
        :options="endpoints"
        :multiple="false"
        :show-labels="false"
        :searchable="true"
        :internal-search="false"
        @search-change="loadEndpoints"
        @open="loadEndpoints"
      >
        <template slot="noResult">
          <slot name="noResult">{{ $t('Not found') }}</slot>
        </template>
        <template slot="noOptions">
          <slot name="noOptions">{{ $t('Not available') }}</slot>
        </template>
      </multiselect>
      <small class="form-text text-muted">Select an endpoint</small>
    </div>
  </div>
</template>


<script>
import ModelerInspector from "../../../../mixins/ModelerInspector";

export default {
  mixins: [ModelerInspector],
  computed: {},
  data() {
    return {
      dataSources: [],
      endpoints: [],
      config: { dataSource: "", endpoint: "create" }
    };
  },
  methods: {
    loadEndpoints() {
      const dataSource = this.$refs.dataSource
        ? this.$refs.dataSource.selectedOption
        : null;
      const endpoints =
        dataSource && dataSource.endpoints ? dataSource.endpoints : {};
      this.endpoints.splice(0);
      for (let name in endpoints) {
        this.endpoints.push(name);
      }
    }
  }
};
</script>
