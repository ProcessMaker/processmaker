<template>
  <div class="card">
    <div class="card-body">
      <div class="form-group col-12">
        <label for="purpose">{{ $t('Data') }}</label>
      </div>
      <b-col cols="12" style="height:12em">
        <monaco-editor
          :options="monacoOptions"
          v-model="rowData.testData"
          language="json"
          class="h-100"
        />
      </b-col>
      <div class="form-group col-12">
        <label for="purpose">{{ $t('Config') }}</label>
      </div>
      <b-col cols="12" style="height:12em">
        <monaco-editor
          :options="monacoOptions"
          v-model="rowData.testConfig"
          language="json"
          class="h-100"
        />
      </b-col>
    </div>
  </div>
</template>

<script>
import Vue from "vue";
import MultiSelect from "vue-multiselect";
import ListHeaders from "./ListHeaders";

Vue.component("multi-select", MultiSelect);
Vue.component("list-headers", ListHeaders);

const methods = ["GET", "POST", "PUT", "PATCH", "DELETE"];

export default {
  inheritAttrs: false,
  props: {
    rowData: {
      type: Object,
      required: true
    },
    rowIndex: {
      type: Number
    },
    canPrint: {
      type: Boolean,
      default: false
    }
  },
  watch: {
    rowData: {
      deep: true,
      handler() {
        if (this.rowData.testData === undefined) {
          this.rowData.testData = "{}";
        }
        if (this.rowData.testConfig === undefined) {
          this.rowData.testConfig = "{}";
        }
      }
    }
  },
  data() {
    return {
      monacoOptions: {
        automaticLayout: true
      },
      methodOptions: methods
    };
  },
  methods: {
    addHeader() {
      let header = {
        id: this.$refs.headersListing.headers.length,
        view: false,
        key: "",
        value: "",
        description: ""
      };
      this.$refs.headersListing.headers.push(header);
      this.$refs.headersListing.fetch();
      this.$refs.headersListing.detail(header);
      this.$set(this.rowData, "headers", this.$refs.headersListing.headers);
    }
  }
};
</script>
