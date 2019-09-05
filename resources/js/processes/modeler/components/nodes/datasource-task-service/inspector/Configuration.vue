<template>
  <div>
    <div class="form-group">
      <label>Name</label>
      <input v-model="definition.name" name="name" placeholder type="text" class="form-control" />
      <small class="form-text text-muted">The name of the data source service</small>
    </div>
    <div class="form-group">
      <label>Data Source</label>
      <select-from-api v-model="config.dataSource" api="datasources" :placeholder="$t('Select a datasource')">
        <template slot="noResult">{{ $t('No data sources found') }}</template>
      </select-from-api>
      <small class="form-text text-muted">Select the data source</small>
    </div>
  </div>
</template>


<script>
import ModelerInspector from "../../../../mixins/ModelerInspector";

export default {
  mixins: [ModelerInspector],
  data() {
    return {
      dataSources: [],
      config: { dataSource: "" }
    };
  }
};
</script>
