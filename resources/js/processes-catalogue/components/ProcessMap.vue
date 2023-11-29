<template>
  <div id="processMap">
    <div id="processData">
      <div
        id="header"
        class="d-flex d-flex justify-content-between"
      >
        <h4 class="d-flex align-items-center">
          <i
            class="fas fa-arrow-circle-left text-secondary mr-2"
            style="font-size: 32px"
          />
          {{ processName }}
        </h4>
        <span class="border border-secondary rounded-circle bg-white">
          <ellipsis-menu
            :actions="processActions"
            :permission="permission"
            :data="processData"
            :is-documenter-installed="isDocumenterInstalled"
            :divider="false"
            @navigate="onProcessNavigate"
          />
        </span>
      </div>
      <p> {{ processDescription }}</p>
    </div>
  </div>
</template>

<script>
import EllipsisMenu from "../../components/shared/EllipsisMenu.vue";

import ellipsisMenuMixin from "../../components/shared/ellipsisMenuActions";
  import processNavigationMixin from "../../components/shared/processNavigation";

export default {
  components: { EllipsisMenu },
  mixins: [ellipsisMenuMixin, processNavigationMixin],
  props: ["processId", "permission", "isDocumenterInstalled"],
  data() {
    return {
      processName: "",
      processDescription: "",
      processData: {},
    };
  },
  mounted() {
    this.getProcess();
  },
  methods: {
    /**
     * get process data
     */
    getProcess() {
      window.ProcessMaker.apiClient
        .get(`processes/${this.processId}`)
        .then((response) => {
          console.log(response);
          this.processData = response.data;
          this.processName = response.data.name;
          this.processDescription = response.data.description;
        });
    },
  },
};
</script>
