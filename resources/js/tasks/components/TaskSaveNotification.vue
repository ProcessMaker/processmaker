<template>
  <div
    class="btn text-black text-capitalize cursor-default mr-3 p-0"
    :style="{ width: '60px' }"
  >
    <div class="toolbar-item d-flex justify-content-center align-items-center">
      <span
        class="mr-1"
        :style="{ fontWeight: 400 }"
      >
        {{ status }}
      </span>
      <span>
        <FontAwesomeIcon class="text-success" :icon="icon" :spin="isLoading" />
      </span>
    </div>
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
  },
  data() {
    return {
      savedIcon: faCheckCircle,
      spinner: faSpinner,
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
