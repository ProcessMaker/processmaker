<template>
  <div>
    <div class="d-flex">
      <label class="flex-grow-1">Form Data</label>
      <button
        type="button"
        class="btn-special-assignment-action btn btn-secondary btn-sm"
        @click="addMapping"
      >+ Data</button>
    </div>
    <table class="table table-striped table-sm">
      <thead>
        <tr>
          <th scope="col">key</th>
          <th scope="col">value</th>
          <th scope="col"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(row,i) in config.dataMapping" :key="i">
          <td>
            <input
              v-model="row.key"
              name="key"
              placeholder="new key"
              type="text"
              class="form-control form-control-sm"
            />
          </td>
          <td>
            <input
              v-model="row.value"
              name="value"
              placeholder="value"
              type="text"
              class="form-control form-control-sm"
            />
          </td>
          <td class="align-middle">
            <a href="javascript:void(0)" class="btn btn-sm btn-danger" @click="removeRowIndex(i)">
              <i class="fa fa-trash-alt"></i>
            </a>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>


<script>
import ModelerInspector from "../../../../mixins/ModelerInspector";

export default {
  mixins: [ModelerInspector],
  data() {
    return {
      field: "",
      config: {dataMapping: []},
    };
  },
  methods: {
    removeRowIndex(index) {
      this.config.dataMapping.splice(index, 1);
    },
    addMapping() {
      this.config.dataMapping.push({ key: "", value: "" });
    }
  }
};
</script>

<style lang="scss" scoped>
.btn-sm {
  border-radius: 2px;
  font-size: 12px;
  font-weight: bold;
  padding: 0px 3px;
}
.form-control-sm {
  border-radius: 2px;
  font-size: 12px;
  padding: 0px 3px;
}
</style>
