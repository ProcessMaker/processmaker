<template>
  <div role="group">
    <label for="retry_wait_time">{{ $t('Retry Wait Time') }}</label>
    <b-form-input id="retry_wait_time" type="number" v-model="config.retry_wait_time" @input="updateConfig" min="0" max="3600"></b-form-input>
    <small class="form-text text-muted">{{ helper }}</small>
  </div>
</template>

<script>

export default {
  props: ['type'],
  data() {
    return {
      config: {
        retry_wait_time: "",
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
          this.config.retry_wait_time = this.valueContent.retry_wait_time;
          this.config.id = this.valueContent.id;
        } else {
          if (configString) {
            const config = JSON.parse(configString);
            this.config.retry_wait_time = _.get(config, 'retry_wait_time');
          }
        }
      } else {
        this.config.id = this.valueContent.id;
      }
    },
    setNodeConfig() {
      const existingSetting = JSON.parse(_.get(this.node(), 'errorHandling', '{}'));
      const json = JSON.stringify({ ...existingSetting, retry_wait_time: this.config.retry_wait_time });
      Vue.set(this.node(), 'errorHandling', json);
    },
    updateConfig() {
      if (this.valueContent.retry_wait_time !== this.config.retry_wait_time) {
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
        return this.$t('Set maximum run retry wait time in seconds. Leave empty to use script default. Set to 0 for no retry wait time.');
      } else if (this.type === 'data-connector') {
        return this.$t('Set maximum run retry wait time in seconds. Leave empty to use data connector default. Set to 0 for no retry wait time.');
      }
    }
  }
}
</script>

<style scoped></style>
