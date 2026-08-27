import Vue from "vue";
import CategorySelect from "../categories/components/CategorySelect";

Vue.component("CategorySelect", CategorySelect);

new Vue({
    el: '#editGroup',
    mixins: addons,
    data() {
        return {
            formData: window.temporal.screen,
            assignedProjects: window.temporal.assignedProjects,
            isDraft: window.temporal.isDraft,
            selectedProjects: '',
            errors: {
                'title': null,
                'type': null,
                'description': null,
                'status': null
            }
        }
    },
    watch: {
        selectedProjects: {
            handler() {
                this.formData.projects = this.selectedProjects;
            }
        }
    },
    methods: {
        resetErrors() {
            this.errors = Object.assign({}, {
                title: null,
                type: null,
                description: null,
                status: null
            });
        },
        onClose() {
          const queryParams = new URLSearchParams(window.location.search);
          const projectId = queryParams.get("project_id");
          window.location.href = projectId ? `/designer/projects/${projectId}`: '/designer/screens';
        },
        onUpdate() {
            if (this.isDraft) {
                ProcessMaker.confirmModal(
                    this.$t("Caution!"),
                    this.$t("You are about to publish a draft version. Are you sure you want to proceed?"),
                    "",
                    () => {
                        this.handleUpdate();
                    }
                );
            } else {
                this.handleUpdate();
            }
        },
        handleUpdate() {
            this.resetErrors();
            ProcessMaker.apiClient.put('screens/' + this.formData.id, this.formData)
                .then(response => {
                    ProcessMaker.alert(this.$t('The screen was saved.'), 'success');
                    this.onClose();
                })
                .catch(error => {
                    if (error.response.status && error.response.status === 422) {
                        this.errors = error.response.data.errors;
                    }
                });
        }
    },
    mounted() {
        this.selectedProjects = this.assignedProjects.length > 0 ?this.assignedProjects.map(project => project.id) : null;
    }
});
