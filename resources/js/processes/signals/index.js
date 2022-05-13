import Vue from 'vue';
import CustomSignalsListing from './components/CustomSignalsListing';
import SystemSignalsListing from './components/SystemSignalsListing';
import CollectionSignalsListing from './components/CollectionSignalsListing';

new Vue({
  el: '#signals-listing',
  components: {
    'custom-signals-listing': CustomSignalsListing,
    'system-signals-listing': SystemSignalsListing,
    'collection-signals-listing': CollectionSignalsListing
  },
  data() {
    return {
      filter: '',
      formData: {},
      disabled: false,
      errors: {
        'name': null,
        'id': null,
      },
    };
  },
  mounted() {
    this.resetFormData();
    this.resetErrors();
  },
  methods: {
    onClose() {
      this.resetFormData();
      this.resetErrors();
    },
    resetFormData() {
      this.formData = Object.assign({}, {
        name: null,
        id: null,
      });
    },
    resetErrors() {
      this.errors = Object.assign({}, {
        name: null,
        id: null,
      });
    },
    reload() {
      this.$refs.signalCustomList.dataManager([{
        field: 'name',
        direction: 'desc',
      }]);
    },
    onSubmit() {
      this.resetErrors();
      //single click
      if (this.disabled) {
        return;
      }
      this.disabled = true;
      ProcessMaker.apiClient.post('signals', this.formData).then(response => {
        ProcessMaker.alert("{{ __('The signal was created.') }}", 'success');
        //redirect list signal
        window.location = '/designer/signals';
      }).catch(error => {
        this.disabled = false;
        //define how display errors
        if (error.response.status && error.response.status === 422) {
          // Validation error
          this.errors = error.response.data.errors;
          //ProcessMaker.alert(this.errors, 'warning');
        }
      });
    }
  }
});
