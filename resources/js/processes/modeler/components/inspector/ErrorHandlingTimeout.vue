<template>
  <div role="group">
    <label for="timeout">{{ $t('Timeout') }}</label>
    <b-form-input id="timeout" type="number" min="0" max="3600" v-model="value" @input="userHasUpdatedValue"></b-form-input>
    <small class="form-text text-muted">{{ helper }}</small>
  </div>
</template>

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
    valueFromNode()
    {
      const configString = _.get(this.node(), 'errorHandling', null);
      const config = JSON.parse(configString);
      return _.get(config, 'timeout', null);
    },
    getNodeConfig(valueFromModel) {
      if (this.valueFromNode()) {
        return;
      }

      this.valueFromModel = this.value = valueFromModel.timeout;
    },
    emitValue() {
      if (!this.userHasUpdated) {
        return;
      }
      const existingSetting = JSON.parse(_.get(this.node(), 'errorHandling', '{}'));
      const json = JSON.stringify({ ...existingSetting, timeout: this.value });
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
        return this.$t('Set maximum run time in seconds. Leave empty to use script default. Set to 0 for no timeout.');
      } else if (this.type === 'data-connector') {
        return this.$t('Set maximum run time in seconds. Leave empty to use data connector default. Set to 0 for no timeout.');
      }
    }
  }
}
</script>

<style scoped></style>
