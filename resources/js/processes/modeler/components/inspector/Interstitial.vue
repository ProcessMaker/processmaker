<template>
  <div>
    <div :class="{'has-error': error}">
      <form-checkbox
        :label="$t('Display Next Assigned Task to Task Assignee')"
        :checked="allowInterstitial"
        :disabled="isDisabled"
        @change="checked"
      />
      <small
        v-if="error"
        class="text-danger"
      >{{ error }}</small>
      <small
        v-if="helper"
        class="form-text text-muted"
      >{{ $t(helper) }}</small>
    </div>
    <screen-select
      v-if="allowInterstitial"
      v-model="screen"
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
import { FormCheckbox } from "@processmaker/vue-form-elements";
import ScreenSelect from "./ScreenSelect.vue";

export default {
  components: { ScreenSelect, FormCheckbox },
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
    this.$root.$on("handle-interstitial", this.handleInterstitial);
  },
  mounted() {
    if (!("allowInterstitial" in this.node)) {
      this.$set(this.node, "allowInterstitial", this.enabledByDefault);
    }
  },
  methods: {
    /**
     * @param {Boolean} checkboxChecked
     */
    checked(checkboxChecked) {
      this.allowInterstitial = checkboxChecked;
    },
    /**
     * Handle interstitial event
     *
     * @param {Object} { nodeId, isDisabled }
     */
    handleInterstitial({ nodeId, isDisabled }) {
      if (nodeId !== this.node.id) {
        return;
      }

      if (isDisabled) {
        this.$set(this.node, "allowInterstitial", false);
      }

      this.isDisabled = isDisabled;
    },
  },
};
</script>
