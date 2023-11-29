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
          h
        </span>
      </div>
      <p> {{ processDescription }}</p>
    </div>
  </div>
</template>

<script>
import EllipsisMenu from "../../components/shared/EllipsisMenu.vue";
import ellipsisMenuMixin from "../../components/shared/ellipsisMenuActions";

export default {
  components: { EllipsisMenu },
  mixins: [ellipsisMenuMixin],
  props: ["processId"],
  data() {
    return {
      processName: "",
      processDescription: "",
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
          console.log(response.data);
          this.processName = response.data.name;
          this.processDescription = response.data.description;
        });
    },
  },
};
</script>
