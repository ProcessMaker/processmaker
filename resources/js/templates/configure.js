import ProcessTemplateConfigurations from '../../js/templates/components/ProcessTemplateConfigurations.vue';
import ScreenTemplateConfigurations from '../../js/templates/components/ScreenTemplateConfigurations.vue';

Vue.component("ProcessTemplateConfigurations", ProcessTemplateConfigurations);
Vue.component("ScreenTemplateConfigurations", ScreenTemplateConfigurations);


new Vue({
    el: '#configureTemplate',
    mixins: addons,
    data() {
        return {
            formData: window.ProcessMaker.templateConfigurations.data,
            screenTypes: window.ProcessMaker.templateConfigurations.screenTypes,
            type: window.ProcessMaker.templateConfigurations.templateType,
            dataGroups: [],
            value: [],
            errors: {
                name: null,
                description: null,
                category: null,
                status: null,
                screen: null
            },
            isDefaultProcessmakerTemplate: false,
        }
    },
    computed: {
        redirectUrl() {
            switch (this.type) {
                case 'process':
                    return '/processes';
                case 'screen':
                        return '/designer/screens';
                default:
                    break;
            }
        }
    },
    methods: {
        resetErrors() {
            this.errors = {};
        },
        onClose() {
            window.location.href = this.redirectUrl;
        },         
        onUpdate() {
            this.resetErrors();
            let that = this;           
            
            ProcessMaker.apiClient.put(`template/settings/${this.type}/${that.formData.id}`, that.formData)
            .then(response => {                
                ProcessMaker.alert(this.$t('The template was saved.'), 'success', 5, true);
                that.onClose();
            })
            .catch(error => {
                //define how display errors
                if (error?.response?.status === 422) {
                    // Validation error
                    that.errors = error.response.data.errors;
                } else if (error?.response?.status === 409) {
                    // Duplicate error
                    that.errors = error.response.data;
                }
            });
        },
        handleUpdatedTemplate(data, templateData) {
            this.formData = data;
            this.isDefaultProcessmakerTemplate = templateData;
        }
    }
});