<template>
  <div
    id="tooltip"
    class="card shadow-sm"
  >
    <div
      v-if="isLoading"
      class="d-flex justify-content-center"
    >
      <div class="spinner-border text-primary" />
    </div>
    <div
      v-else
      class="card-body"
      style="padding-top: 10px; padding-bottom: 5px"
    >
      <div v-if="shouldShowTokenResult">
        <p class="tooltip-title">
          <span class="text-info">{{ tokenResult.element_name }}</span>
        </p>
        <p class="tooltip-data">
          <span class="tooltip-data-title">{{ $t('Status') }}:</span>
          <span class="text-secondary">{{ tokenResult.status_translation }}</span>
        </p>
        <p class="tooltip-data">
          <span class="tooltip-data-title">{{ $t('Completed By') }}:</span>
          <span class="text-secondary">{{ tokenResult.completed_by }}</span>
        </p>
        <p class="tooltip-data">
          <span class="tooltip-data-title">{{ $t('Time Started') }}:</span>
          <span class="text-secondary">{{ tokenResult.created_at_formatted }}</span>
        </p>
        <p class="tooltip-data">
          <span class="tooltip-data-title">{{ $t('Time Completed') }}:</span>
          <span class="text-secondary">{{ tokenResult.completed_at_formatted }}</span>
        </p>
      </div>
      <div v-if="shouldShowTokenResultSequenceFlow">
        <p class="tooltip-title">
          <span class="text-info">{{ nodeName }}</span>
        </p>
        <p class="tooltip-data">
          <span class="tooltip-data-title">
            {{ tokenResult.repeat_message }}
          </span>
        </p>
      </div>
      <div v-if="shouldShowTokenResultMessage">
        <p class="tooltip-title">
          <span class="text-info">{{ nodeName }}</span>
        </p>
        <p class="tooltip-data">
          <span class="tooltip-data-title">{{ tokenResult.message }}</span>
        </p>
      </div>
    </div>
  </div>
</template>
<script>
export default {
  name: "ProcessMapTooltip",
  props: {
    enabled: {
      type: Boolean,
      default() {
        return true;
      },
    },
    nodeId: {
      type: String,
      default() {
        return "";
      },
    },
    nodeName: {
      type: String,
      default() {
        return "";
      },
    },
    requestId: {
      type: Number,
      default() {
        return null;
      },
    },
  },
  data() {
    return {
      isLoading: false,
      tokenResult: {},
    };
  },
  computed: {
    shouldShowTokenResult() {
      return !Object.hasOwn(this.tokenResult, "message") && !this.tokenResult.is_sequence_flow;
    },
    shouldShowTokenResultSequenceFlow() {
      return !Object.hasOwn(this.tokenResult, "message") && this.tokenResult.is_sequence_flow;
    },
    shouldShowTokenResultMessage() {
      return Object.hasOwn(this.tokenResult, "message");
    },
  },
  watch: {
    nodeId() {
      if (this.enabled === false) {
        return;
      }
      this.getRequestTokens();
    },
    isLoading(value) {
      this.$emit("is-loading", value);
    },
  },
  methods: {
    getRequestTokens() {
      this.isLoading = true;
      ProcessMaker.apiClient.get(`requests/${this.requestId}/tokens`, {
        params: {
          element_id: this.nodeId,
        },
      })
        .then((response) => {
          this.tokenResult = response.data;
        })
        .catch(() => {
          this.tokenResult = {
            message: this.$t("No information found."),
          };
        })
        .finally(() => {
          this.isLoading = false;
        });
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
  margin-bottom: 5px;
  font-weight: bold;
  font-size: 16px;
  line-height: 24px;
  letter-spacing: -0.02em;
}
.tooltip-data-title {
  font-weight: bold;
  padding-right: 5px;
}
.tooltip-data {
  font-size: 14px;
  line-height: 21px;
  letter-spacing: -0.02em;
  padding-top: 0px;
  padding-bottom: 0px;
  margin-bottom: 5px;
}
</style>
