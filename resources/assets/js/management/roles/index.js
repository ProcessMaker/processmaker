import Vue from 'vue'
import RolesListing from './components/RolesListing'
import ValidationErrors from './../../components/common/mixins/ValidationErrors'
import FormInput from './../../components/common/forms/FormInput'
import FormSelect from './../../components/common/forms/FormSelect'
import FormCheckbox from './../../components/common/forms/FormCheckbox'



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
  components: { RolesListing, FormInput, FormSelect, FormCheckbox },
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
