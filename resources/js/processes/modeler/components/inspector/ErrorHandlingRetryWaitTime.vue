<template>
  <div role="group">
    <label for="retry_wait_time">{{ $t('Retry Wait Time') }}</label>
    <b-form-input id="retry_wait_time" type="number" v-model="config.retry_wait_time"></b-form-input>
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
    getNodeConfig() {
      const configString = _.get(this.node(), 'errorHandling', null);
      if (configString) {
        const config = JSON.parse(configString);
        this.config.retry_wait_time = _.get(config, 'retry_wait_time', "");
      }
    },
    setNodeConfig() {
      const json = JSON.stringify({ retry_wait_time: this.config.retry_wait_time });
      Vue.set(this.node(), 'errorHandling', json);
    },
  },
  mounted() {
    this.getNodeConfig();
  },
  computed: {
    helper() {
      if (this.type === 'script') {
        return this.$t('Set maximum run time in seconds. Leave empty to use script default. Set to 0 for no retry wait time.');
      } else if (this.type === 'data-connector') {
        return this.$t('Set maximum run time in seconds. Leave empty to use data connector default. Set to 0 for no retry wait time.');
      }
    }
  }
}
</script>

<style scoped></style>
