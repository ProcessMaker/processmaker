import Vue from 'vue'
import RolesListing from './components/RolesListing'
import ValidationErrors from './../../components/common/mixins/ValidationErrors'

// Bring in our form elements
import FormElements from '@processmaker/vue-form-elements'
Vue.use(FormElements);


// Bootstrap our Designer application
new Vue({
  mixins: [ValidationErrors],
  el: '#roles-listing',
  data: {
      filter: '' ,
      addRoleCode: '',
      addRoleName: '',
      addRoleDescription: '',
      addRoleStatus: 'ACTIVE',
      test: ''
  },
  components: { 
    RolesListing, 
  },
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

      })
      .catch((err) => {
        // @todo Replace with new flashy errors?
        this.updateValidationErrors(err.response.data.errors);
      })
    }
  }
})
