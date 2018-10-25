<template>
  <vue-form-renderer @submit="submit" v-model="formData" :config="form" />
</template>

<script>
import VueFormRenderer from "@processmaker/vue-form-builder/src/components/vue-form-renderer";


export default {
  components: {
    VueFormRenderer
  },
  props: [
    'processId',
    'instanceId',
    'tokenId',
    'form',
    'data'
  ],
  data() {
    return {
      formData: this.data
    };
  },
  mounted() {
  },
  methods: {
    submit() {
      var self = this;
      ProcessMaker.apiClient.put(
          'tasks/' + this.tokenId +
          '?status=COMPLETED',
          this.formData)
        .then(function() {
          document.location.href = '/tasks';
        });
    },
    update(data) {
      this.formData = data;
    }
  }
}
</script>

<style lang="scss" scoped>
</style>
