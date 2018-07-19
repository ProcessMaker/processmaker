import Vue from 'vue'
import UsersListing from './components/UsersListing'

import {
  FormInput
} from '@processmaker/vue-form-elements/src/components'

// Bootstrap our User listing
new Vue({
  data: {
    filter: '',
    addUser: {
      username: '',
      firstname: '',
      lastname: '',
      password: ''
    },
    addUserValidationError: null,
    addUserValidationErrors: {
      username: null,
      firstname: null,
      lastname: null,
      password: null
    },
    addUserPasswordConfirmation: '',
  },
  el: '#users-listing',
  computed: {
    addUserPasswordMismatch() {
      if(this.addUser.password != this.addUserPasswordConfirmation) {
        return 'Confirmation password must match'
      } else {
        return null
      }
    }
  },
  methods: {
   openAddUserModal() {
      this.$refs.addModal.show()
    },
    hideAddModal() {
      this.$refs.addModal.hide()
      this.resetAddUser()
    },
    resetAddUser() {
      // Reset form data:
      this.addUser.username = '';
      this.addUser.firstname = '';
      this.addUser.lastname = '';
      this.addUser.password = '';
      this.addUserPasswordConfirmation = '';
      this.addUserValidationError = null;
      this.addUserValidationErrors.username = null;
      this.addUserValidationErrors.firstname = null;
      this.addUserValidationErrors.lastname = null;
      this.addUserValidationErrors.password = null;
    },
    submitAdd() {
      if(this.addUserPasswordMismatch) {
        this.addUserValidationError = "You must correct the data before continuing"
        return
      }
      this.addUserValidationError = null
      ProcessMaker.apiClient.post('users', this.addUser)
        .then((response) => {
          this.$refs.addModal.hide()
          this.resetAddUser()
          ProcessMaker.alert('New User Successfully Created', 'success')
          // Refresh data grid
          this.$refs.listing.sortOrder = [
            {
              field: "created_at",
              sortField: "created_at",
              direction: "desc"
            }
          ];
          this.$refs.listing.orderBy = 'created_at'
          this.$refs.listing.orderDirection = 'desc'
          this.$refs.listing.fetch();
        })
        .catch((error) => {
          if(error.response.status == 422) {
            // Validation error
            this.addUserValidationError = error.response.data.message;
            let fields = Object.keys(error.response.data.errors);
            for(var field of fields) {
              this.addUserValidationErrors[field] = error.response.data.errors[field][0];

            }
          }
        });
    }
  },
  components: { 
    UsersListing ,
    FormInput
  }
})