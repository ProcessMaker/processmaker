<template>
    <div class="form-group">
      <label >{{ $t('Notify Process Manager') }}</label>
      <b-form-checkbox switch id="switch_inapp" type="checkbox" v-model="config.switchInApp">
        {{ $t('In-app Notification') }}
      </b-form-checkbox>
      <b-form-checkbox switch id="switch_email" type="checkbox" v-model="config.switchEmail">
        {{ $t('Email Notification') }}
      </b-form-checkbox>
    </div>
  </template>
  
  <script>
  export default {
    props: ['type'],
    data() {
      return {
        config: {
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
          this.config.switchInApp = _.get(config, 'inapp_notification', "");
          this.config.switchEmail = _.get(config, 'email_notification', "");
        }
      },
      setNodeConfig() {
        const json = JSON.stringify({ 
          inapp_notification: this.config.switchInApp,
          email_notification: this.config.switchEmail
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
  