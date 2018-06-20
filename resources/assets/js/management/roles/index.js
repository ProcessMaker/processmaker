import Vue from 'vue'
import RolesListing from './components/RolesListing'
// import RolesTest from '/tests/Feature/Api/Administration/RolesTest'

// Bootstrap our Designer application
new Vue({
  el: '#roles-listing',
  data: {
      filter: '' ,
      addRoleCode: '',
      addRoleName: '',
      addRoleDescription: '',
      addRoleStatus: 'ACTIVE',
      errors: {}
  },
  components: { RolesListing },
  watch: {
    errors: function(val){
      this.displayError(this.errors)
    }
  },
  methods: {
    showAddModal() {
      this.$refs.addModal.show();
    },
    hideAddModal() {
      this.$refs.addModal.hide();
    },
    displayError(errors){
      for (var error_field in errors) {
        console.log('Form Element ID: '+ error_field)
        console.log('Error Message: '+ errors[error_field][0])
     }
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

      })
      .catch((err) => {
        console.log(err.response)
        this.errors = err.response.data.errors
        // @todo Replace with new flashy errors?
        // alert('There was a problem creating the role.')
      })
    }
  }
})