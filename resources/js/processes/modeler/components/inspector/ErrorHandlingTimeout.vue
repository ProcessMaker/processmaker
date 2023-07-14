<template>
  <div role="group">
    <label for="timeout">{{ $t('Timeout') }}</label>
    <b-form-input id="timeout" type="number" min="0" max="3600" v-model="config.timeout" @input="updateConfig"></b-form-input>
    <small class="form-text text-muted">{{ helper }}</small>
  </div>
</template>

<script>

export default {
  props: ['type'],
  data() {
    return {
      config: {
        timeout: "",
      },
      valueContent: ""
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
          this.config.timeout = this.valueContent.timeout;
          this.config.id = this.valueContent.id;
        } else {
          if (configString) {
            const config = JSON.parse(configString);
            this.config.timeout = _.get(config, 'timeout');
          }
        }
      } else {
        this.config.id = this.valueContent.id;
        if (!configString) {
          this.config.timeout = this.valueContent.timeout;
        }
        if (this.valueContent.method) {
            this.config.timeout = this.valueContent.timeout;
            if (this.config.id !== this.valueContent.id) {
              this.config.timeout = this.valueContent.timeout;
              this.config.id = this.valueContent.id;
            } else {
              if (configString) {
                const config = JSON.parse(configString);
                this.config.timeout = _.get(config, 'timeout');
              }
            }
        }
      }
    },
    setNodeConfig() {
      const existingSetting = JSON.parse(_.get(this.node(), 'errorHandling', '{}'));
      const json = JSON.stringify({ ...existingSetting, timeout: this.config.timeout });
      Vue.set(this.node(), 'errorHandling', json);
    },
    updateConfig() {
      if (this.valueContent.timeout !== this.config.timeout) {
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
        return this.$t('Set maximum run time in seconds. Leave empty to use script default. Set to 0 for no timeout.');
      } else if (this.type === 'data-connector') {
        return this.$t('Set maximum run time in seconds. Leave empty to use data connector default. Set to 0 for no timeout.');
      }
    }
  }
}
</script>

<style scoped></style>
