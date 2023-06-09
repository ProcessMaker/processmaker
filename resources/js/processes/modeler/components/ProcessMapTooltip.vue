<template>
  <div
    id="tooltip"
    class="card shadow-sm"
  >
    <div
      v-if="isLoading"
      class="d-flex justify-content-center"
    >
      <div
        class="spinner-border text-primary"
        role="status"
      />
    </div>
    <div
      v-if="!isLoading"
      class="card-body"
      style="padding-top: 10px; padding-bottom: 5px"
    >
      <p class="tooltip-title">
        <span class="text-info">{{ nodeId }}</span>
      </p>
      <p class="tooltip-data">
        <span class="tooltip-data-title">{{ $t('Status') }}:</span><span class="text-secondary">Complete</span>
      </p>
      <p class="tooltip-data">
        <span class="tooltip-data-title">{{ $t('Completed By') }}:</span><span class="text-secondary">UserName</span>
      </p>
      <p class="tooltip-data">
        <span class="tooltip-data-title">{{ $t('Time Started') }}:</span><span class="text-secondary">11/21/23 16:51</span>
      </p>
      <p class="tooltip-data">
        <span class="tooltip-data-title">{{ $t('Time Completed') }}:</span><span class="text-secondary">11/21/23 16:53</span>
      </p>
    </div>
  </div>
</template>
<script>
export default {
  name: "ProcessMapTooltip",
  props: {
    nodeId: {
      type: String,
      default() {
        return "";
      },
    },
  },
  data() {
    return {
      isLoading: false,
    };
  },
  watch: {
    nodeId() {
      this.getInformation();
    },
    isLoading(value) {
      this.$emit("is-loading", value);
    },
  },
  mounted() {
    //
  },
  methods: {
    getInformation() {
      this.isLoading = true;
      setTimeout(() => {
        this.isLoading = false;
      }, 2000);
    },
  },
};

</script>
<style>
#tooltip {
  position: absolute;
  z-index: 3;
}
.tooltip-title {
  font-weight: bold;
  font-size: 90%;
  margin-bottom: 5px;
}
.tooltip-data-title {
  font-weight: bold;
  padding-right: 5px;
}
.tooltip-data {
  padding-top: 0px;
  padding-bottom: 0px;
  margin-bottom: 5px;
  font-size: 80%;
}
</style>
