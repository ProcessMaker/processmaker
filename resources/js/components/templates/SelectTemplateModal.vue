<template>
  <div>
    <b-button :aria-label="$t('Create Process')" v-b-modal.selectTemplate class="mb-3 mb-md-0 ml-md-2">
      <i class="fas fa-plus"/> {{ $t('Process') }}
    </b-button>
    <modal
      id="selectTemplate"
      size="huge"
      :title="$t('New [[Type]]')"
      :subtitle="$t('Start a new [[type]] from a blank canvas or select a template')"
      @ok.prevent="onSubmit"
    >
      <b-button :aria-label="$t('Create Blank [[Type]]')" class="mb-3 blank-template-btn">
        <i class="fas fa-plus" /> {{ $t('Blank [[Type]]') }}
      </b-button>
      <template-search />
    </modal>
  </div>
</template>

<script>
  import { Modal } from "SharedComponents";
  import TemplateSearch from "./TemplateSearch.vue";

  export default {
    components: { Modal, TemplateSearch },
    mixins: [ ],
    props: [],
    data: function() {
      return {
      }
    },
    methods: {}
  };
</script>

<style scoped>
  .blank-template-btn {
    float: right;
    background-color: #1572C2;
  }
</style>