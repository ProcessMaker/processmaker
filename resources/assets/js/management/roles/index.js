import Vue from 'vue'
import RolesListing from './components/RolesListing'

// Bootstrap our Designer application
new Vue({
  el: '#roles-listing',
  data: {
      filter: '' ,
      addRoleCode: '',
      addRoleName: '',
      addRoleDescription: '',
      addRoleStatus: 'ACTIVE'
  },
  components: { RolesListing },
  methods: {
    showAddModal() {
      this.$refs.addModal.show();
    },
    hideAddModal() {
      this.$refs.addModal.hide();
    },
    submitAdd() {
      // todo
    }
  }
})