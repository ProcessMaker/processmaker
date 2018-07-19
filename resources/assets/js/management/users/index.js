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
    passwordConfirmation: ''
  },
  el: '#users-listing',
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
      this.passwordConfirmation = '';
    },
    submitAdd() {
      ProcessMaker.apiClient.post('users', this.addUser)
        .then((response) => {
          // Add notification to the top
          this.$refs.addModal.hide()
          this.resetAddUser()
          // @todo Use new notifications
          // Refresh data grid
          this.$refs.listing.sortOrder = [
            {
              field: "created_at",
              sortField: "created_at",
              direction: "desc"
            }
          ];
          this.$refs.listing.fetch();
        });
    }
  },
  components: { 
    UsersListing ,
    FormInput
  }
})