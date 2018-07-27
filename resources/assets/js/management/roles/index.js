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
      window.ProcessMaker.apiClient.post('roles', {
        'name': this.addRoleName,
        'code': this.addRoleCode,
        'description': this.addRoleDescription,
        'status': this.addRoleStatus
      })
      .then((response) => {
        // Close modal
        this.$refs.addModal.hide();
        // Status is 200, so let's just change our sort and sort direction and then fetch
        this.$refs.rolesListing.dataManager([
          {
            field: 'created_at',
            direction: 'desc'
          }
        ])
      });
    }
  }
})