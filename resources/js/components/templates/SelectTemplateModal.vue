<template>
    <modal
      id="selectTemplate"
      size="huge"
      :title="$t('New Process')"
      :ok-disabled="disabled"
      @ok.prevent="onSubmit"
      @hidden="onClose"
    >
    <template-search></template-search>
    </modal>
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
