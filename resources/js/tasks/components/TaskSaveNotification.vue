<template>
  <div>
    <div
      class="btn text-black text-capitalize cursor-default"
      :style="{ width: '20px' }"
    >
      <div class="toolbar-item d-flex justify-content-center align-items-center">
        <span>
          <FontAwesomeIcon v-if="task.draft" class="text-success" :icon="icon" :spin="isLoading" />
        </span>
      </div>
    </div>
    <a class="lead text-secondary font-weight-bold">
      {{ task.element_name }}
    </a>
    <b-tooltip
      v-if="task.draft"
      target="saved-status"
      custom-class="auto-save-tooltip"
    >
      <div class="tooltip-case-title">
        {{ task.process_request.case_title }}
      </div>
      <div class="tooltip-draft-date">
        <FontAwesomeIcon class="text-success" :icon="savedIcon" />
        {{ $t('Last Autosave: ')+date }}
      </div>
    </b-tooltip>
  </div>
</template>
<script>
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faCheckCircle, faSpinner } from "@fortawesome/free-solid-svg-icons";

export default {
  components: { FontAwesomeIcon },
  props: {
    options: {
      type: Object,
      default: function () {
        return {
          is_loading: false,
        };
      },
    },
    task: {},
    date: "",
  },
  data() {
    return {
      savedIcon: faCheckCircle,
      spinner: faSpinner,
      lastAutosave: "",
    };
  },
  computed: {
    isLoading() {
      return this.options.is_loading;
    },
    status() {
      const status = this.isLoading ? "Saving" : "Saved";
      return this.$t(status);
    },
    icon() {
      return this.isLoading ? this.spinner : this.savedIcon;
    },
  },
};
</script>
<style>
.auto-save-tooltip {
  opacity: 1 !important;
}
.auto-save-tooltip .tooltip-inner {
  background-color: #FFFFFF;
  color: #566877;
  box-shadow: -5px 5px 5px rgba(0, 0, 0, 0.3);
  max-width: 250px;
  padding: 14px;
  border-radius: 7px;
  border: 1px solid rgba(0, 0, 0, 0.125);
}
.auto-save-tooltip .arrow::before {
  border-bottom-color: #CDDDEE !important;
  border-top-color: #CDDDEE !important;
}
.tooltip-case-title {
  font-weight: 700;
  font-size: 16px;
}
.tooltip-draft-date {
  font-weight: 400;
  font-size: 16px;
}
</style>
