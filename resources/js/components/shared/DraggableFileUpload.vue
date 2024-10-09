<template>
  <div>
    <uploader
      :options="options"
      ref="uploader"
      @file-success="fileUploaded"
      @file-added="addFile"
    >
      <uploader-unsupport></uploader-unsupport>

      <uploader-drop id="uploaderMain" class="dotted-border p-4">
        <i class="fas fa-file-upload fa-3x fa-fw text-secondary mb-1"></i>
        <div>
          {{ $t('Drag file here') }} <br />
          - {{ $t('or') }} -
        </div>
        <uploader-btn id="submitFile" class="text-primary">{{ $t('Select file from computer') }}</uploader-btn>
        <div v-if="$refs.uploader && inProgress || loadingFile">
          <i class="fas fa-spinner fa-spin p-0" />
        </div>
      </uploader-drop>

      <uploader-list v-if="displayUploaderList">
        <template slot-scope="{ fileList }">
          <ul>
            <li v-for="file in fileList" :key="file.id">
              <uploader-file :file="file" :list="true"></uploader-file>
            </li>
          </ul>
        </template>
      </uploader-list>
    </uploader>
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
  props: [ "options", "accept", 'displayUploaderList'],
  computed: {
    inProgress() {
      return this.$refs.uploader.fileList.some(file => file._prevProgress < 1);
    },
  },
  data() {
    return {
      loadingFile: false
    };
  },
  methods: {
    addFile(file) {
      this.loadingFile = false;
      if (this.accept) {
        file.ignored = true;
        if (this.accept.indexOf(file.fileType) !== -1) {
          file.ignored = false;
        }
        if (file.ignored) {
          ProcessMaker.alert(this.$t("The selected file is invalid or not supported. Please verify that this file is in JSON format."), "danger");
          return false
        }
        this.loadingFile = true;
      }
      file.ignored = false;
    
      return true;
    },
    fileUploaded(rootFile, file, message) {
      this.loadingFile = false;
      this.$emit('input', file.file);
    },
  }
};
</script>

<style scoped>
.required {
  color: red;
  font-size: 0.8em;
}

.dotted-border {
  border: 3px dotted #e0e0e0;
}

#uploaderMain {
  font-size: 18px;
}

.uploader-drop {
  background: none;
}

.uploader-btn:hover {
  background:none;
}

#submitFile {
  border:none;
}
</style>