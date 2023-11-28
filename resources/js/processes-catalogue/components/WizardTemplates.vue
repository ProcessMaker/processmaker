<template>
    <div class="container wizard-template-container">
        <template-search
        type="wizard"
        package-ai="false"
        :component="currentComponent" 
        @show-details="showDetails($event)"
        :showTemplateOptionsActionBar="false"
        ></template-search>
    </div>
</template>

<script>
import TemplateSearch from "../../components/templates/TemplateSearch.vue";

export default {
    components: { TemplateSearch },
    data() {
        return {
            currentComponent: 'template-select-card',
            templateData: {},
        }
    },
    methods: {
        showDetails($event) {
            this.templateData = $event;
            this.currentComponent = 'template-details';
      },
    }    
}
</script>

