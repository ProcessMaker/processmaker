<template>
  <div>
    <b-button :aria-label="createProcess" v-b-modal.selectTemplate class="mb-3 mb-md-0 ml-md-2">
      <i class="fas fa-plus"/> {{ process }}
    </b-button>
    <modal
      id="selectTemplate"
      size="huge"
      :title="title"
      :subtitle="subtitle"
      :hasHeaderButtons="hasHeaderButtons"
      :headerButtons="headerButtons"
      :hasTitleButtons="hasTitleButtons"
      :titleButtons="titleButtons"
      :hide-footer="true"
      @showSelectTemplate="showSelectTemplateComponent"
      @ok.prevent="onSubmit"
      @close="close"
    >
      <template-search :type="type" :component="currentComponent" @show-details="updateModal($event)"/>
    </modal>
  </div>
</template>

<script>
  import { Modal } from "SharedComponents";
  import TemplateSearch from "./TemplateSearch.vue";

  export default {
    components: { Modal, TemplateSearch},
    props: ['type'],
    data: function() {
      return {
        title: '',
        showHelper: true,
        currentComponent: 'template-select-card',
        hasTitleButtons: true,
        hasHeaderButtons: false,
        headerButtons: [
          {'content': '< Back', 'action': 'showSelectTemplate', 'variant': 'link', 'disabled': false, 'hidden': true, 'ariaLabel': 'Back to select templates'},
        ],
        titleButtons: [
          {'content': `Blank ${this.type}`, 'action': 'createBlankProcess', 'variant': 'primary', 'disabled': false, 'hidden': false, 'position': 'right', 'icon': 'fas fa-plus', 'ariaLabel': `Create ${this.type}`},
          {'content': 'Use Template', 'action': 'useSelectedTemplate', 'variant': 'primary', 'disabled': false, 'hidden': true, 'position': 'right', 'ariaLabel': `Create a ${this.type} with this template` },
        ],
      }
    },
    computed: {
      subtitle() {
        return this.$t(`Start a new ${this.type} from a blank canvas or select a template`);
      },
      createProcess() {
        return this.$t('Create Process');
      },
      process() {
        return this.$t('Process');
      }
    },
    methods: {
      updateModal($event) {
        this.title = $event.title;
        this.hasHeaderButtons = true;
        this.headerButtons[0].hidden = false;
        this.titleButtons[0].hidden = true;
        this.titleButtons[1].hidden = false;
        this.currentComponent = 'template-details';
      },
      showSelectTemplateComponent() {
        this.currentComponent = 'template-select-card';
        this.headerButtons[0].hidden = true;
        this.titleButtons[0].hidden = false;
        this.titleButtons[1].hidden = true;
        this.hasHeaderButtons = false;
        this.title = this.$t(`New ${this.type}`);
      },
      close() {
        this.$bvModal.hide("selectTemplate");
        this.currentComponent = 'template-select-card';
      },
    },
    mounted() {
      this.title = this.$t(`New ${this.type}`);
    }
  };
</script>

<style scoped>

</style>