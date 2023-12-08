<template>
  <div>
    <breadcrumbs
      ref="breadcrumb"
      :category="selectCategory"
      :process="process.name"
    />
    <modal-save-version ref="modalSave" :options="dataOptions"></modal-save-version>
    <b-row>
      <div class="d-flex">
        <b-col cols="9">
          <process-map
            :process="process"
            :permission="permission"
            :current-user-id="currentUserId"
            :is-documenter-installed="isDocumenterInstalled"
            @goBackCategory="goBackCategory"
          />
          <processes-carousel
            :process="process"
          />
        </b-col>
        <b-col cols="3">
          <process-options :process="process" />
        </b-col>
      </div>
      <b-col cols="12">
        <process-tab></process-tab>
      </b-col>
    </b-row>
  </div>
</template>

<script>
import MenuCatologue from "./menuCatologue.vue";
import ProcessesCarousel from "../components/ProcessesCarousel.vue";
import ProcessMap from "./ProcessMap.vue";
import ProcessOptions from "./ProcessOptions.vue";
import Breadcrumbs from "./Breadcrumbs.vue";
import ProcessTab from './ProcessTab.vue';
import ModalSaveVersion from '../../components/shared/ModalSaveVersion.vue';
import { Modeler, ValidationStatus } from "@processmaker/modeler";

export default {
  components: {
    ProcessOptions,
    Breadcrumbs,
    ProcessMap,
    MenuCatologue,
    ProcessesCarousel,
    ProcessTab,
    ModalSaveVersion,
    Modeler,
    ValidationStatus,
  },
  props: ["process", "permission", "isDocumenterInstalled", "currentUserId"],
  data() {
    return {
      listCategories: [],
      selectCategory: 0,
      dataOptions: {},
    };
  },
  created() {
    this.selectCategory = this.selectedCategory();
    this.getCategories();
  },
  mounted(){   
    this.dataOptions = {
      id: this.process.id.toString(),
      type: "Process",
    };
  },
  methods: {
    /** Rerun a process cards */
    goBackCategory() {
      this.$emit("goBackCategory");
    },
  },
};
</script>
