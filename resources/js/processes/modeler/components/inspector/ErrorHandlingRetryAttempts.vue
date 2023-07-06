<template>
  <div role="group">
    <label for="retry_attempts">{{ $t('Retry Attempts') }}</label>
    <b-form-input id="retry_attempts" type="number" v-model="config.retry_attempts"  @input="updateConfig" min="0" max="50"></b-form-input>
    <small class="form-text text-muted">{{ helper }}</small>
  </div>
</template>

<script>

export default {
  props: ['type'],
  data() {
    return {
      config: {
        retry_attempts: "",
      },
      valueContent: "",
    }
  },
  watch: {
    config: {
      deep: true,
      handler() {
        this.setNodeConfig();
      }
    },
  },
  methods: {
    node() {
      return this.$root.$children[0].$refs.modeler.highlightedNode.definition;
    },
    getNodeConfig(newValue) {
      this.valueContent = newValue;
      const configString = _.get(this.node(), 'errorHandling', null);
      if (this.config.id) {
        if (this.config.id !== this.valueContent.id) {
          this.config.retry_attempts = this.valueContent.retry_attempts;
          this.config.id = this.valueContent.id;
        } else {
          if (configString) {
            const config = JSON.parse(configString);
            this.config.retry_attempts = _.get(config, 'retry_attempts');
          }
        }
      } else {
        this.config.id = this.valueContent.id;
        if (!configString) {
          this.config.retry_attempts = this.valueContent.retry_attempts;
        }
      }
    },
    setNodeConfig() {
      const existingSetting = JSON.parse(_.get(this.node(), 'errorHandling', '{}'));
      const json = JSON.stringify({ ...existingSetting, retry_attempts: this.config.retry_attempts });
      Vue.set(this.node(), 'errorHandling', json);
    },
    updateConfig() {
      if (this.valueContent.retry_attempts !== this.config.retry_attempts) {
        this.setNodeConfig();
      }
    },
  },
  mounted() {
    this.$root.$on("contentChanged", this.getNodeConfig);
  },
  beforeDestroy() {
    this.$root.$off("contentChanged", this.getNodeConfig);
  },
  computed: {
    helper() {
      if (this.type === 'script') {
        return this.$t('Set maximum run retry attempts in seconds. Leave empty to use script default. Set to 0 for no retry attempts.');
      } else if (this.type === 'data-connector') {
        return this.$t('Set maximum run retry attempts in seconds. Leave empty to use data connector default. Set to 0 for no retry attempts.');
      }
    }
  }
}
</script>

<style scoped></style>
