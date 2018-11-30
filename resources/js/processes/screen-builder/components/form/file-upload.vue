<template>
  <div>
    <label v-uni-for="name">{{label}}</label>
    <uploader :options="options" ref="uploader">
      <uploader-unsupport></uploader-unsupport>
      <uploader-drop id="uploaderMain" class="form-control-file">
        <p>Drop files here to upload or</p>
        <uploader-btn id="submitFile" class="btn btn-secondary">select files</uploader-btn>
      </uploader-drop>
      <uploader-list></uploader-list>
    </uploader>
    <div class="invalid-feedback" v-if="error">{{error}}</div>
    <small v-if="helper" class="form-text text-muted">{{helper}}</small>
  </div>
</template>


<script>
import { createUniqIdsMixin } from "vue-uniq-ids";
import uploader from "vue-simple-uploader";

// Create the mixin
const uniqIdsMixin = createUniqIdsMixin();

export default {
  components: uploader,
  mixins: [uniqIdsMixin],
  props: ["label", "error", "helper", "name", "value", "controlClass"],
  mounted() {
    // we need to be able to remove the classes from the npm package
    var element = document.getElementById("submitFile");
    element.classList.remove("uploader-btn");

    var element = document.getElementById("uploaderMain");
    element.classList.remove("uploader-drop");

    //emit message when upload happens
    const uploaderInstance = this.$refs.uploader.uploader;
    uploaderInstance.on("fileSuccess", (rootFile, file, message, chunk) => {
      message = JSON.parse(message).fileUploadId;
      this.$emit("input", message);
    });
  },
  computed: {
    classList() {
      let classList = {
        "is-invalid":
          (this.validator && this.validator.errorCount) || this.error
      };
      if (this.controlClass) {
        classList[this.controlClass] = true;
      }
      return classList;
    }
  },
  data() {
    return {
      content: "",
      validator: null,
      requestID: null,
      options: {
        target: this.getTargetUrl,
        // We cannot increase this until laravel chunk uploader handles this gracefully
        simultaneousUploads: 1,
        query: {
          chunk: true
        },
        testChunks: false,
        // Setup our headers to deal with API calls
        headers: {
          "X-Requested-With": "XMLHttpRequest",
          "X-CSRF-TOKEN":
            window.ProcessMaker.apiClient.defaults.headers.common[
              "X-CSRF-TOKEN"
            ]
        },
        singleFile: true
      }
    };
  },
  methods: {
    updateValue(e) {
      this.content = e.target.value;
      this.$emit("input", this.content);
    },
    getTargetUrl() {
      this.requestID = document.head.querySelector(
        'meta[name="request-id"]'
      ).content;
      return "/api/1.0/requests/" + this.requestID + "/files";
    }
  }
};
</script>

<style lang="scss" scoped>
</style>