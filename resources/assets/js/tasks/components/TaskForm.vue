<template>
<div class="container">
  <h1>{{loading === false ? 'Task Details' : 'Loading Task Details...'}}</h1>  
  <div class="row" v-if="loading === false">
    <div class="col-8">
      <div class="card card-body">
        <vue-form-renderer @submit="submit" v-model="formData" :config="json" />
      </div>
    </div>
    <div class="col-4">
      <div class="card card-body">
        <h4>Debug</h4>
        processUid: {{processUid}}<br>
        instanceUid: {{instanceUid}}<br>
        tokenUid: {{tokenUid}}<br>
        formUid: {{formUid}}<br>
        data: {{data}}
      </div>
    </div>
  </div>
</div>
</template>

<script>
import VueFormRenderer from "@processmaker/vue-form-builder/src/components/vue-form-renderer";


export default {
  components: {
    VueFormRenderer
  },
  props: [
    'processUid',
    'instanceUid',
    'tokenUid',
    'formUid',
    'data',
  ],
  data() {
    return {
      loading: true,
      json: [{
        name: "Default",
        items: []
      }],
      formData: this.data
    };
  },
  mounted() {
    this.fetch();
  },
  methods: {
    submit() {
      var self = this;
      ProcessMaker.apiClient.post(
          'processes/' + this.processUid +
          '/instances/' + this.instanceUid +
          '/tokens/' + this.tokenUid +
          '/complete',
          this.formData)
        .then(function() {
          document.location.href = '/tasks?successfulRouting=true';
        });
    },
    update(data) {
      this.formData = data;
    },
    fetch() {
      this.loading = true;

      // Load from our api client
      ProcessMaker.apiClient
        .get(
          "process/" +
          this.processUid +
          "/form/" +
          this.formUid
        )
        .then(response => {
          this.json = response.data.content;
          this.loading = false;
        });
    }
  }
}
</script>

<style lang="scss" scoped>
</style>
