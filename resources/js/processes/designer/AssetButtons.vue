<template>
  <b-card-text>
    <div class="button-container">
      <slot
        name="select-template-slot"
        class="custom-slot"
      ></slot>
    </div>
    <div class="button-container">
      <b-button
        variant="primary"
        size="sm"
        class="custom-button custom-text"
        block
        @click="callURL()"
      >
        {{ asset_name_all }}
        <i class="fas fa-arrow-right ml-2 align-middle"></i>
      </b-button>
    </div>
  </b-card-text>
</template>

<script>
import SelectTemplateModal from "../../components/templates/SelectTemplateModal.vue";
import CreateProcessModal from "../components/CreateProcessModal.vue";
import ProcessesListing from "../components/ProcessesListing.vue";
import CategorySelect from "../categories/components/CategorySelect.vue";
import CreateScriptModal from "../../../js/processes/scripts/components/CreateScriptModal.vue";

export default {
  components: {
    SelectTemplateModal,
    CreateProcessModal,
    ProcessesListing,
    CategorySelect,
    CreateScriptModal,
  },
  props: {
    asset_name_all: String,
    urlPath: String,
  },
  data() {
    return {
      countCategories: 2,
    };
  },
  methods: {
    callURL() {
      window.location.href = window.location.origin + this.urlPath;
    },
  },
};
</script>

<style scoped>
.button-container {
  display: block;
  width: 100%;
  margin-bottom: 10px;
}
.b-card {
  border: none;
  padding: 0;
}
.b-card .btn {
  width: 100%;
}
</style>
