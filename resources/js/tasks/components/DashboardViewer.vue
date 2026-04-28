<template>
  <div>
    <vue-form-renderer
      v-model="draftFormData"
      :config="screen.config"
      :computed="screen.computed"
      :custom-css="screen.custom_css"
      :watchers="screen.watchers"
    />
  </div>
</template>

<script>
import cloneDeep from "lodash/cloneDeep";

export default {
  props: ["screen", "formData"],
  data() {
    return {
      disabled: false,
      draftFormData: {},
    };
  },
  watch: {
    screen: {
      deep: true,
      handler() {
        this.disabled = false;
      },
    },
    formData: {
      immediate: true,
      deep: true,
      handler(val) {
        this.draftFormData = cloneDeep(val || {});
      },
    },
  },
  methods: {},
};
</script>
