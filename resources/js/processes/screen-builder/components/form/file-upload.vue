<template>
  <div>
    <label v-uni-for="name">{{ label }}</label>
    <b-card v-if="mode === 'preview'" class="mb-2">
      {{ $t('File uploads are unavailable in preview mode.') }}
    </b-card>
    <uploader
      v-else
      :key="renderKey"
      :options="options"
      :attrs="attrs"
      ref="uploader"
      @complete="complete"
      @upload-start="start"
      @file-removed="removed"
      @file-success="fileUploaded"
      @file-added="addFile"
    >
      <uploader-unsupport></uploader-unsupport>

      <uploader-drop id="uploaderMain" class="form-control-file">
        <p>{{ $t('Drop a file here to upload or') }}</p>
        <uploader-btn id="submitFile" class="btn btn-secondary text-white">{{ $t('select file') }}</uploader-btn>
      </uploader-drop>

      <uploader-list>
        <template slot-scope="{ fileList }">
          <ul>
            <li v-if="fileList.length === 0 && value">
              <i class="fas fa-paperclip"></i> {{ value }}
            </li>
            <li v-for="file in fileList" :key="file.id">
              <uploader-file :file="file" :list="true"></uploader-file>
            </li>
          </ul>
        </template>
      </uploader-list>
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
  props: ["label", "error", "helper", "name", "value", "controlClass", "endpoint", "accept"],
  beforeMount() {
    this.getFileType();
  },
  updated() {
    this.removeDefaultClasses();
  },
  mounted() {
    this.removeDefaultClasses();

    // If we're in a record list, this will fire to give us the prefix
    this.$root.$on('set-upload-data-name', (recordList, index) => {
      if (index === null) {
        // Adding new record
        index = recordList.value ? recordList.value.length : 0;
      }
      const prefix = recordList.name + "." + index.toString() + ".";
      this.setFileUploadNameForChildren(recordList.$children, prefix);
    })
  },
  computed: {
    renderKey() {
      return this.prefix + this.name;
    },
    mode() {
      return this.$root.$children[0].mode;
    },
    classList() {
      return {
        "is-invalid": (this.validator && this.validator.errorCount) || this.error,
        [this.controlClass]: !!this.controlClass,
      }
    },
    inProgress() {
      return this.$refs.uploader.fileList.some(file => file._prevProgress < 1);
    },
    filesAccept() {
      if (!this.accept) {
        return null;
      }

      let accept = [];

      (this.accept.split(',')).forEach(item => {
        accept.push(item.trim())
      });
      return accept;
    }
  },
  watch: {
    name: {
      handler() {
        this.options.query.data_name = this.prefix + this.name;
      },
      immediate: true,
    },
    prefix: {
      handler() {
        this.options.query.data_name = this.prefix + this.name;
      },
      immediate: true,
    },
  },
  data() {
    return {
      content: "",
      fileType: null,
      validator: {
        errorCount: 0,
        errors: [],
      },
      prefix: '',
      options: {
        target: this.getTargetUrl,
        // We cannot increase this until laravel chunk uploader handles this gracefully
        simultaneousUploads: 1,
        query: {
          chunk: true,
          data_name: this.name
        },
        testChunks: false,
        // Setup our headers to deal with API calls
        headers: {
          "X-Requested-With": "XMLHttpRequest",
          "X-CSRF-TOKEN": window.ProcessMaker.apiClient.defaults.headers.common["X-CSRF-TOKEN"]
        },
        singleFile: true
      },
      attrs: {
        accept: this.accept
      },
    };
  },
  methods: {
    setFileUploadNameForChildren(children, prefix) {
      children.forEach(child => {
        if (_.get(child, '$options.name') === 'FileUpload') {
          child.prefix = prefix;
        } else if (_.get(child, '$children', []).length > 0) {
          this.setFileUploadNameForChildren(child.$children, prefix);
        }
      });
    },
    addFile(file) {
      if (this.filesAccept) {
        file.ignored = true;
        if (this.filesAccept.indexOf(file.fileType) !== -1) {
          file.ignored = false;
        }
        if (file.ignored) {
          ProcessMaker.alert(this.$t("File not allowed."), "danger");
          return false
        }
      }
      file.ignored = false;
      return true;
    },
    removeDefaultClasses() {
      // we need to be able to remove the classes from the npm package
      document
        .querySelectorAll("[id='submitFile'],[id='uploaderMain']")
        .forEach(element => {
          element.classList.remove("uploader-btn", "uploader-drop");
        });
    },
    getFileType() {
      if (document.head.querySelector('meta[name="collection-id"]')) {
        this.fileType = 'collection';
      } else {
        this.fileType = 'request';
      }
    },
    fileUploaded(rootFile, file, message) {
      if (this.fileType == 'request') {
        this.$emit("input", file.name);
      }

      if (this.fileType == 'collection') {
        message = JSON.parse(message);
        this.$emit("input", {
          id: message.id,
          name: message.file_name
        });
      }
    },
    removed() {
      if (!this.inProgress) {
        this.complete();
      }
    },
    complete() {
      // Unblock submit
      this.validator.errorCount = 0;
      window.onbeforeunload = function() {};
    },
    start() {
      // Block submit until files are loaded
      this.validator.errorCount = 1;
      window.onbeforeunload = function() {
        return true;
      };
    },
    getTargetUrl() {
      if (this.endpoint) {
        return this.endpoint;
      }

      if (this.fileType == 'request') {
        const requestIDNode = document.head.querySelector('meta[name="request-id"]');

        return requestIDNode
          ? `/api/1.0/requests/${requestIDNode.content}/files`
          : null;
      }

      if (this.fileType == 'collection') {
        const collectionIdNode = document.head.querySelector('meta[name="collection-id"]');

        return collectionIdNode
          ? '/api/1.0/files' +
            '?model=' +
            'ProcessMaker\\Plugins\\Collections\\Models\\Collection' +
            '&model_id=' +
            collectionIdNode.content +
            '&collection=' +
            'collection'
          : null;
      }
    }
  }
};
</script>
