<template>
  <div>
    <component
      :is="driverKeyToComponent(driverKey)"
      :form-data="formData"
      @updateFormData="updateFormData"
    />
  </div>
</template>

<script>
import ExcelConnectionProperties from "./cdata/ExcelConnectionProperties.vue";
import GithubConnectionProperties from "./cdata/GithubConnectionProperties.vue";
import DocusignConnectionProperties from "./cdata/DocusignConnectionProperties.vue";
import GmailConnectionProperties from "./cdata/GmailConnectionProperties.vue";
import BamboohrConnectionProperties from "./cdata/BamboohrConnectionProperties.vue";
import SapHanaConnectionProperties from "./cdata/SapHanaConnectionProperties.vue";

export default {
  components: {
    ExcelConnectionProperties,
    GithubConnectionProperties,
    DocusignConnectionProperties,
    GmailConnectionProperties,
    BamboohrConnectionProperties,
    SapHanaConnectionProperties,
  },
  props: {
    formData: {
      type: Object,
      default: () => ({}),
    },
    driverKey: {
      type: String,
      default: "",
    },
  },
  data() {
    return {
      componentsMap: {
        "cdata.excel": "excel-connection-properties",
        "cdata.github": "github-connection-properties",
        "cdata.docusign": "docusign-connection-properties",
        "cdata.gmail": "gmail-connection-properties",
        "cdata.BambooHR": "bamboohr-connection-properties",
        "cdata.saphana": "sap-hana-connection-properties",
      },
    };
  },
  methods: {
    updateFormData(val) {
      this.$emit("updateFormData", val);
    },
    driverKeyToComponent(driverKey) {
      return this.componentsMap[driverKey] || null;
    },
  },
};
</script>
