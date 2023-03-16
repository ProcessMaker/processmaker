<template>
  <div>
    <b-button :aria-label="$t('Create Process')" v-b-modal.selectTemplate class="mb-3 mb-md-0 ml-md-2">
      <i class="fas fa-plus"/> {{ $t('Process') }}
    </b-button>
    <modal
      id="selectTemplate"
      size="huge"
      :title="$t(`New ${type}`)"
      :subtitle="$t(`Start a new ${type} from a blank canvas or select a template`)"
      :hide-footer="true"
      @ok.prevent="onSubmit"
      @click="showTemplateDetailsModal"
    >
      <b-button :aria-label="$t(`Create Blank ${type}`)" class="mb-3 blank-template-btn">
        <i class="fas fa-plus" /> {{ $t(`Blank ${type}`) }}
      </b-button>
      <template-search />
    </modal>
    <template-details-modal />
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
      }
    },
    methods: {
        showTemplateDetailsModal() {
        this.$bvModal.show('templateDetails');
      },
    }
  };
</script>

<style scoped>
  .blank-template-btn {
    float: right;
    background-color: #1572C2;
  }
</style>