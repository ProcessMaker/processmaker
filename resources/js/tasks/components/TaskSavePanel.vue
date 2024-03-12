<template>
  <div>
    <div
      class="btn text-black text-capitalize cursor-default"
      :style="{ width: '20px' }"
    >
      <div class="toolbar-item d-flex justify-content-center align-items-center">
        <span>
          <FontAwesomeIcon
            v-if="task.draft"
            :class="{ 'text-success': !error, 'text-secondary': error }"
            :icon="icon"
            :spin="isLoading"
          />
        </span>
      </div>
    </div>
    <span class="autosave-title">
      {{ $t('AUTOSAVE') }}
    </span>
    <div class="autosave-draft-date">
      {{ $t('Last save: ')+date }}
    </div>
    <div v-if="error" class="autosave-draft-error">
      {{ $t('Unable to save: Verify your internet connection.') }}
    </div>
  </div>
</template>
<script>
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faCheckCircle, faSpinner, faExclamationTriangle } from "@fortawesome/free-solid-svg-icons";

export default {
  components: { FontAwesomeIcon },
  props: {
    options: {
      type: Object,
      default() {
        return {
          is_loading: false,
        };
      },
    },
    task: {
      type: Object,
      default() {
        return {};
      },
    },
    date: {
      type: String,
      default() {
        return "-";
      },
    },
    error: {
      type: Boolean,
      default() {
        return false;
      },
    },
  },
  data() {
    return {
      savedIcon: faCheckCircle,
      spinner: faSpinner,
      errorIcon: faExclamationTriangle,
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
      if (this.error) {
        return this.errorIcon;
      }

      if (this.isLoading) {
        return this.spinner;
      }

      return this.savedIcon;
    },
  },
};
</script>
  <style>
  .autosave-title {
    font-weight: 700;
    font-size: 14px;
  }
  .autosave-draft-date {
    font-weight: 400;
    font-size: 16px;
  }
  .autosave-draft-error {
    font-style: italic;
    font-weight: 400;
    font-size: 16px;
  }
  </style>
