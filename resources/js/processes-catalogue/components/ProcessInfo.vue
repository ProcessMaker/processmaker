<template>
  <div>
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
      <hr class="my-12">
      <process-tab
        :current-user="currentUser"
        :process="process"
      />
    </b-col>
  </div>
</template>

<script>
import ProcessesCarousel from "./ProcessesCarousel.vue";
import ProcessMap from "./ProcessMap.vue";
import ProcessOptions from "./ProcessOptions.vue";
import ProcessTab from "./ProcessTab.vue";

export default {
  components: {
    ProcessOptions,
    ProcessMap,
    ProcessesCarousel,
    ProcessTab,
  },
  props: ["process", "permission", "isDocumenterInstalled", "currentUserId", "currentUser"],
  data() {
    return {
      listCategories: [],
      selectCategory: 0,
      dataOptions: {},
    };
  },
  mounted() {
    this.dataOptions = {
      id: this.process.id.toString(),
      type: "Process",
    };
  },
  methods: {
    /**
     * Return a process cards
     */
    goBackCategory() {
      this.$emit("goBackCategory");
    },
  },
};
</script>
