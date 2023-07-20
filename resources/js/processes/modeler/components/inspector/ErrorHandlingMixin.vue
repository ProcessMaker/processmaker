<script>
export default {
  props: ['type'],
  data() {
    return {
      value: "",
      valueFromModel: "",
      userHasUpdated: false,
    }
  },
  watch: {
    value: {
      handler() {
        this.emitValue();
      }
    },
  },
  methods: {
    node() {
      return this.$root.$children[0].$refs.modeler.highlightedNode.definition;
    },
    valueFromNode() {
      const configString = _.get(this.node(), 'errorHandling', null);
      const config = JSON.parse(configString);
      return _.get(config, this.configKey, null);
    },
    getNodeConfig(valueFromModel) {
      if (this.valueFromNode()) {
        return;
      }

      this.valueFromModel = this.value = valueFromModel[this.configKey];
    },
    emitValue() {
      if (!this.userHasUpdated) {
        return;
      }
      const existingSetting = JSON.parse(_.get(this.node(), 'errorHandling', '{}'));
      const json = JSON.stringify({ ...existingSetting, [this.configKey]: this.value });
      Vue.set(this.node(), 'errorHandling', json);
    },
    userHasUpdatedValue() {
      this.userHasUpdated = true;
    },
  },
  mounted() {
    this.value = this.valueFromNode();
    this.$root.$on("contentChanged", this.getNodeConfig);
  },
  beforeDestroy() {
    this.$root.$off("contentChanged", this.getNodeConfig);
  },
  computed: {
    helper() {
      if (this.type === 'script') {
        return this.$t(this.scriptHelperText);
      } else if (this.type === 'data-connector') {
        return this.$t(this.dataConnectorHelperText);
      }
    }
  }
}
</script>