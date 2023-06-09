<template>
  <div class="form-group">
    <label for="timeout">{{ $t('Timeout') }}</label>
    <b-form-input id="timeout" type="number" v-model="config.timeout"></b-form-input>
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
        switchInApp: false,
        switchEmail: false,
      },
    }
  },
  watch: {
    config: {
      deep: true,
      handler() {
        this.setNodeConfig();
        this.$emit("input", this.config.timeout);
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
        this.config.timeout = _.get(config, 'timeout', "");
      }
    },
    setNodeConfig() {
      const json = JSON.stringify({ 
        timeout: this.config.timeout,
      });
      Vue.set(this.node(), 'errorHandling', json);
    },
  },
  mounted() {
    this.getNodeConfig();
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
