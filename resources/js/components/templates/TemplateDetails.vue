<template>
  <div class="template-details-container">
    <wizard-template-details
      v-if="type === 'wizard'"
      :template="template"
    />
    <default-template-details
      v-else
      :template="template"
    />
  </div>
</template>

<script>
import WizardTemplateDetails from "./WizardTemplateDetails.vue";
import DefaultTemplateDetails from "./DefaultTemplateDetails.vue";

  export default {
    components: { WizardTemplateDetails, DefaultTemplateDetails },
    mixins: [],
    props: ["template", "type"],
    data: function() {
      return {
      }
    },
    methods: {
    },
    computed: {
    },
    mounted() {
    },
  };
</script>

<style scoped>

</style>
