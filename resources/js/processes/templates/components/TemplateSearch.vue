<template>
  <div>
    <div class="pb-2">
      <basic-search />
    </div>
    <div d-flex justify-content-between class="pb-2">
      <b-card-group deck>
        <template-select-card />
        <template-select-card />
        <template-select-card />
        <template-select-card />
      </b-card-group>
    </div>
  </div>
</template>

<script>
import TemplateSelectCard from "./TemplateSelectCard.vue";
import BasicSearch from "../../../components/shared/BasicSearch.vue";

export default {
  components: { TemplateSelectCard, BasicSearch },
  props: [],
  data() {
    return {
    };
  },
  computed: {},
  watch: {},
  beforeMount() {},
  methods: {},
};
</script>
