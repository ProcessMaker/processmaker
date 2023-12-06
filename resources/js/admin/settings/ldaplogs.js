import LdapLogs from "./components/LdapLogs.vue";

new Vue({
  el: "#ldap-logs",
  components: { LdapLogs },
  data: {
    filter: "",
  },
  methods: {
    reload() {
      this.$refs.listing.dataManager();
    },
  },
});
