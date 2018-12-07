<template>
  <div>
    <div v-for="file in files.data" v-if="loaded">
      <div @click="onClick">
        <i class="fas fa-download fa-lg"></i>
        <a>Download {{file.file_name}}</a>
      </div>
    </div>
  </div>
</template>


<script>
export default {
  data() {
    return {
      loaded: false,
      files: {},
      requestId: null
    };
  },
  beforeMount() {
    this.getRequestId();
  },
  mounted() {
    this.getFiles();
  },
  methods: {
    onClick() {
      console.log("downloading");
      ProcessMaker.apiClient
        .get("request/" + this.requestId + "/files/" + this.files.data[0].id)
        .then(response => {
          console.log("HEllo");
        });
    },
    getRequestId() {
      this.requestId = document.head.querySelector(
        'meta[name="request-id"]'
      ).content;
    },
    getFiles() {
      ProcessMaker.apiClient
        .get("requests/" + this.requestId + "/files")
        .then(response => {
          this.files = response.data;
          this.loaded = true;
        });
    }
  }
};
</script>

<style lang="scss" scoped>
</style>