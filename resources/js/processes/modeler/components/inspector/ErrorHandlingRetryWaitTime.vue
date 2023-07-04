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
    actualizarValorContent(nuevoValor) {
      this.valorContent = nuevoValor; // Actualizar el valor de la variable en este componente
      const configString = _.get(this.node(), 'errorHandling', null);
      console.log("🚀 ~ file: ErrorHandlingRetryWaitTime.vue:32 ~ actualizarValorContent ~ configString:", configString)
      if (configString) {
        const config = JSON.parse(configString);
        this.config.retry_wait_time = _.get(config, 'retry_wait_time');
      } else {
        this.config.retry_wait_time = this.valorContent.retry_wait_time;
      }
    },
    node() {
      return this.$root.$children[0].$refs.modeler.highlightedNode.definition;
    },
    getNodeConfig(newValue) {
      this.valueContent = newValue; // Actualizar el valor de la variable en este componente
      const configString = _.get(this.node(), 'errorHandling', null);
      if (configString) {
        const config = JSON.parse(configString);
        this.config.retry_wait_time = _.get(config, 'retry_wait_time');
      } else {
        this.config.retry_wait_time = this.valueContent.retry_wait_time;
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
    this.$root.$on("contentChanged", this.actualizarValorContent); // Escuchar evento personalizado
    this.getNodeConfig();
  },
  beforeDestroy() {
    this.$root.$off("contentChanged", this.actualizarValorContent); // Dejar de escuchar el evento al destruir el componente
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
