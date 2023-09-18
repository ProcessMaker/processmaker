<template>
    <div class="form-group">
      <label >{{ $t('Notify Process Manager') }}</label>
      <b-form-checkbox switch id="switch_inapp" type="checkbox" v-model="config.inapp_notification">
        {{ $t('In-app Notification') }}
      </b-form-checkbox>
      <b-form-checkbox switch id="switch_email" type="checkbox" v-model="config.email_notification">
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
          inapp_notification: false,
          email_notification: false,
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
          this.config.inapp_notification = _.get(config, 'inapp_notification', "");
          this.config.email_notification = _.get(config, 'email_notification', "");
        }
      },
      setNodeConfig() {
        const existingSetting = JSON.parse(_.get(this.node(), 'errorHandling', '{}'));
        const json = JSON.stringify({ 
          ...existingSetting, 
          inapp_notification: this.config.inapp_notification, 
          email_notification: this.config.email_notification 
        });
        Vue.set(this.node(), 'errorHandling', json);
      },
    },
    mounted() {
      this.getNodeConfig();
    },
    computed: {
      helper() {
        return this.$t('Notify Process Manager');
      }
    }
  }
  </script>
  
  <style scoped></style>
  
