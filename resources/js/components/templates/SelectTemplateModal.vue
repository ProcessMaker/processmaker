<template>
  <div>
    <b-button :aria-label="$t('Create Process')" v-b-modal.selectTemplate class="mb-3 mb-md-0 ml-md-2">
      <i class="fas fa-plus"/> {{ $t('Process') }}
    </b-button>
    <modal
      id="selectTemplate"
      size="huge"
      :hide-footer="true"
      @ok.prevent="onSubmit"
      @click="showTemplateDetailsModal"
    >
      <div>
        <div class="d-flex justify-content-between" vertical-align="center" align-h="between">
          <h5 class="modal-title">
            {{ $t(`New ${type}`)}}
            <span class="text-muted subtitle d-block">{{ $t(`Start a new ${type} from a blank canvas or select a template`) }}</span>
          </h5>
          <b-button :aria-label="$t(`Create Blank ${type}`)" class="mb-3 blank-template-btn" variant="primary">
            <i class="fas fa-plus" /> {{ $t(`Blank ${type}`) }}
          </b-button>
        </div>
      </div>
      <template-search :templates="templates" @show-details="showTemplateDetailsModal($event)"/>
    </modal>
    <template-details-modal id="templateDetails" :template="template"/>
  </div>
</template>

<script>
  import { Modal } from "SharedComponents";
  import TemplateSearch from "./TemplateSearch.vue";
  import TemplateDetailsModal from "./TemplateDetailsModal.vue";

  export default {
    components: { Modal, TemplateSearch, TemplateDetailsModal },
    mixins: [ ],
    props: ['type'],
    data: function() {
      return {
        templates: [],
        template: {},
      }
    },
    beforeMount() {
      ProcessMaker.apiClient
      .get("templates/" + this.type.toLowerCase())
      .then((response) => {
        if (response?.data?.data) {
          this.templates = response.data.data;
        }
      });
    },
    methods: {
      showTemplateDetailsModal($event) {
        this.template = $event.template;
        this.$bvModal.hide('selectTemplate');
        this.$bvModal.show('templateDetails');
      },
    }
  };
</script>

<style scoped>

</style>