<template>
  <div>
    <div v-if="!allowInterstitial" class="alert alert-info task-info-alert">
      "{{ $t('Display Next Assigned Task to Task Assignee') }}" {{ $t('option has been moved to the task destination dropdown') }}
    </div>
    <screen-select
      v-if="allowInterstitial"
      v-model="screen"
      name="interstitialScreen"
      :label="$t('Screen Interstitial')"
      :required="true"
      :helper="$t('What Screen Should Be Used For Rendering This Interstitial')"
      :params="parameters"
      default-key="interstitial"
    />
  </div>
</template>

<script>
import { get } from "lodash";
import ScreenSelect from "./ScreenSelect.vue";

export default {
  components: { ScreenSelect },
  props: {
    label: {
      type: String,
      default: "",
    },
    helper: {
      type: String,
      default: "",
    },
    enabledByDefault: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      screen: null,
      loading: false,
      error: "",
      parameters: {
        type: "DISPLAY",
      },
      isDisabled: false,
    };
  },
  computed: {
    /**
     * Get the value of the edited property
     */
    allowInterstitial: {
      get() {
        const { node } = this;
        // Get the value of allowInterstitial or set it to true if it hasn't been defined yet.
        const value = get(node, "allowInterstitial", this.enabledByDefault);

        this.screen = get(node, "interstitialScreenRef");
        return value;
      },
      /**
       * Update allowInterstitial property
       */
      set(value) {
        this.$set(this.node, "allowInterstitial", value);
      },
    },

    node() {
      return this.$root.$children[0].$refs.modeler.highlightedNode.definition;
    },
  },
  watch: {
    screen: {
      handler(value) {
        this.$set(this.node, "interstitialScreenRef", value);
      },
    },
  },
  created() {
    // Listen for elementDestination interstitial event
    this.$root.$on("handle-task-interstitial", this.handleInterstitial);
  },
  mounted() {
    if (!("allowInterstitial" in this.node)) {
      this.$set(this.node, "allowInterstitial", this.enabledByDefault);
    }
  },
  methods: {
    /**
     * Handle interstitial event
     *
     * @param {Object} { nodeId, isDisabled }
     */
    handleInterstitial({ nodeId, show }) {
      if (nodeId !== this.node.id) {
        return;
      }
      if (show) {
        this.$set(this.node, "allowInterstitial", true);
      } else {
        this.$set(this.node, "allowInterstitial", false);
      }
    },

  },
};
</script>

<style lang="scss" scoped>
.task-info-alert {
  background-color: #EAF2FF;
}
</style>
