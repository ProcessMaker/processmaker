<template>
  <div>
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
        Process Tab
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

export default {
  components: {
    ProcessOptions,
    Breadcrumbs,
    ProcessMap,
    MenuCatologue,
    ProcessesCarousel,
  },
  props: ["process", "permission", "isDocumenterInstalled", "currentUserId"],
  data() {
    return {
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
