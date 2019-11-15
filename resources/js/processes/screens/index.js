import Vue from 'vue'
import ScreenListing from './components/ScreenListing'
import CategorySelect from "../categories/components/CategorySelect";

Vue.component('category-select', CategorySelect);

new Vue({
    el: '#screenIndex',
    data: {
        filter: '',
        screenModal: false,
        screenId: null,
        addScreen:  {
            formData: {},
            errors: {
                'title': null,
                'type': null,
                'description': null,
                'category': null,
            },
            disabled: false,
        }
    },
    components: {
        ScreenListing,
    },
    methods: {
        goToImport() {
          window.location = '/designer/screens/import';
        },
        show() {
            this.screenId = null;
            this.screenModal = true;
        },
        reload() {
            this.$refs.screenListing.dataManager([
                {
                    field: 'updated_at',
                    direction: 'desc'
                }
            ]);
        },
        resetFormData() {
            this.addScreen.formData = Object.assign({}, {
              title: null,
              type: '',
              description: null,
            });
          },
        resetErrors() {
            this.addScreen.errors = Object.assign({}, {
                title: null,
                type: null,
                description: null,
            });
        },
        onClose() {
            this.resetFormData();
            this.resetErrors();
        },
        onSubmit() {
            this.resetErrors();
            //single click
            if (this.addScreen.disabled) {
                return
            }
            this.addScreendisabled = true;
            ProcessMaker.apiClient.post('screens', this.addScreen.formData)
            .then(response => {
                ProcessMaker.alert(this.$t('The screen was created.'), 'success');
                window.location = '/designer/screen-builder/' + response.data.id + '/edit';
            })
            .catch(error => {
                this.addScreen.disabled = false;
                if (error.response.status && error.response.status === 422) {
                    this.addScreen.errors = error.response.data.errors;
                }
            });
        }
    }
});
