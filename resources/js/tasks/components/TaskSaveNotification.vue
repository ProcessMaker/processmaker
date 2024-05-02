<template>
  <div>
    <div
      class="btn text-black text-capitalize cursor-default"
    >
      <div class="toolbar-item d-flex align-items-center">
        <span>
          <FontAwesomeIcon
            v-if="task.draft"
            :class="{ 'text-success': !error, 'text-secondary': error }"
            :icon="icon"
            :spin="isLoading"
          />
        </span>
        <span
          id="saved-status"
          class="element-name truncate-text"
          :style="{
            maxWidth: `${size}px`
          }"
          v-html="sanitize(task.element_name)"
        >
        </span>
      </div>
    </div>
    <b-tooltip
      v-if="task.draft"
      target="saved-status"
      custom-class="auto-save-tooltip"
    >
      <div class="tooltip-case-title">
        {{ task.process_request.case_title }}
      </div>
      <div class="tooltip-draft-date">
        <FontAwesomeIcon v-if="!error" class="text-success" :icon="savedIcon" />
        {{ $t('Last Autosave: ')+date }}
      </div>
      <div v-if="error" class="tooltip-draft-error">
        <FontAwesomeIcon v-if="error" class="text-secondary" :icon="errorIcon " />
        <span>{{ $t('Unable to save. Verify your internet connection.') }}
        </span>
      </div>
    </b-tooltip>
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
    formData: {
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
    size: {
      type: Number,
      default() {
        return 0;
      },
    },
  },
  data() {
    return {
      savedIcon: faCheckCircle,
      spinner: faSpinner,
      errorIcon: faExclamationTriangle,
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
  mounted() {
    console.log(this.task);
  },
  methods: {
    sanitize(html) {
      return this.removeScripts(html);
    },
    removeScripts(input) {
      const doc = new DOMParser().parseFromString(input, 'text/html');

      const scripts = doc.querySelectorAll('script');
      scripts.forEach((script) => {
        script.remove();
      });

      const styles = doc.querySelectorAll('style');
      styles.forEach((style) => {
        style.remove();
      });

      return doc.body.innerHTML;
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
}
.tooltip-case-title {
  font-weight: 700;
  font-size: 16px;
}
.tooltip-draft-date {
  font-weight: 400;
  font-size: 16px;
}
.tooltip-draft-error {
  font-style: italic;
  font-weight: 400;
  font-size: 16px;
}
.auto-save-tooltip .arrow::after {
  content: "";
  position: absolute;
  bottom: 0;
  border-width: 0 .4rem .4rem;
  transform: translateY(3px);
  border-color: transparent;
  border-style: solid;
  border-bottom-color: #FFFFFF;
}
.element-name {
  font-size: 16px;
  font-weight: bold;
  color: #566877;
  margin-left: 5px;
}
.element-name a {
  color: inherit;
}
.truncate-text {
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}
</style>
