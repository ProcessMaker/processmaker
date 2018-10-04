import Vue from 'vue'
import VariablesListing from './components/VariablesListing'

import {
  FormInput
} from '@processmaker/vue-form-elements/src/components'

// Bootstrap our Variables listing
new Vue({
  data: {
    filter: '',
    addVariable: {
      name: '',
      description: '',
      value: ''
    },
    addVariableValidationError: null,
    addVariableValidationErrors: {
      name: null,
      description: null,
      value: null
    },
  },
  el: '#variables-listing',
  methods: {
   openAddVariableModal() {
      this.$refs.addModal.show()
    },
    hideAddModal() {
      this.$refs.addModal.hide()
      this.resetAddVariable()
    },
    resetAddVariable() {
      // Reset form data:
      this.addVariable.name = '';
      this.addVariable.description = '';
      this.addVariable.value = '';
      this.addVariableValidationError = null;
      this.addVariableValidationErrors.name = null;
      this.addVariableValidationErrors.description = null;
      this.addVariableValidationErrors.value = null;
    },
    submitAdd() {
      this.addVariableValidationError = null
      ProcessMaker.apiClient.post('environment-variables', this.addVariable)
        .then((response) => {
          this.$refs.addModal.hide()
          this.resetAddVariable()
          ProcessMaker.alert('New Variable Successfully Created', 'success')
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
            let fields = Object.keys(error.response.data.errors);
            for(var field of fields) {
              this.addVariableValidationErrors[field] = error.response.data.errors[field][0];
            }
          }
        });
    }
  },
  components: { 
    VariablesListing ,
    FormInput
  }
})