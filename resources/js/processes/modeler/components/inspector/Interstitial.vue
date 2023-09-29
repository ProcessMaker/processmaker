<template>
  <div>
    <div :class="{'has-error': error}">
      <form-checkbox
        :label="$t('Display Next Assigned Task to Task Assignee')"
        :checked="allowInterstitialGetter"
        @change="allowInterstitialSetter"
      />
      <small v-if="error" class="text-danger">{{ error }}</small>
      <small v-if="helper" class="form-text text-muted">{{ $t(helper) }}</small>
    </div>
    <screen-select
      v-if="allowInterstitialGetter"
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
import ScreenSelect from "./ScreenSelect";

export default {
  components: { ScreenSelect },
  props: ["value", "label", "helper", "enabledByDefault"],
  data() {
    return {
      screen: null,
      loading: false,
      error: "",
      parameters: {
        type: "DISPLAY",
      },
    };
  },
  computed: {
    /**
     * Get the value of the edited property
     */
    allowInterstitialGetter() {
      const { node } = this;

      // Get the value of allowInterstitial or set it to true if it hasn't been defined yet.
      const value = _.get(node, "allowInterstitial", this.enabledByDefault || false);

      this.screen = _.get(node, "interstitialScreenRef");
      return value;
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
    allowInterstitialGetter: {
      handler(value) {
        this.allowInterstitialSetter(value);
      },
      immediate: true,
    },
  },
  methods: {
    /**
     * Update allowInterstitial property
     */
    allowInterstitialSetter(value) {
      this.$set(this.node, "allowInterstitial", value);
    },
  },
};
</script>
