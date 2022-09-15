<template>
  <div>
    <uploader
      :options="options"
      ref="uploader"
      @file-success="fileUploaded"
      @file-added="addFile"
    >
      <uploader-unsupport></uploader-unsupport>

      <uploader-drop id="uploaderMain" class="form-control-file p-4">
        <i class="fas fa-file-upload fa-3x fa-fw"></i>
        <p>{{ $t('Drag file here') }}</p>
        <p>- {{ $t('or') }} -</p>
        <uploader-btn id="submitFile" class="btn btn-link">{{ $t('Select file from computer') }}</uploader-btn>
      </uploader-drop>

      <uploader-list>
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
  props: [ "options", "name", "accept"],
  computed: {
    inProgress() {
      return this.$refs.uploader.fileList.some(file => file._prevProgress < 1);
    },
  },
  data() {
    return {
    };
  },
  methods: {
    addFile(file) {
      if (this.accept) {
        file.ignored = true;
        if (this.accept.indexOf(file.fileType) !== -1) {
          file.ignored = false;
        }
        if (file.ignored) {
          ProcessMaker.alert(this.$t("File not allowed."), "danger");
          return false
        }
      }
      file.ignored = false;
      if (!this.name) {
        this.options.query.data_name = file.name
      }
      
      return true;
    },
    fileUploaded(rootFile, file, message) {
        console.log('ROOT FILE', rootFile);
        console.log('file', file);
        console.log('MESSAGE', message);
        this.$emit('input', rootFile.file);
    },
  }
};
</script>

<style scoped>
.required {
  color: red;
  font-size: 0.8em;
}

.form-control-file {
    border: 4px dotted #e0e0e0;
}
</style>