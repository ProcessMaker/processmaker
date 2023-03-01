<template>
  <div d-flex justify-content-between>
    <b-card-group deck>
      <template-select-card />
      <template-select-card />
      <template-select-card />
      <template-select-card />
    </b-card-group>
  </div>
</template>

<script>
import TemplateSelectCard from "./TemplateSelectCard.vue";

export default {
  components: { TemplateSelectCard },
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
