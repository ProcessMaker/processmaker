import LdapLogs from './components/LdapLogs';

new Vue({
  components: { LdapLogs },
  el: '#ldap-logs',
  data: {
    filter: '',
  },
  methods: {
    reload() {
      this.$refs.listing.dataManager();
    },
  },
});
