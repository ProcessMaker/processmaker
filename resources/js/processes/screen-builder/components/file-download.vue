<template>
  <div>
    <b-card v-if="mode === 'preview'" class="mb-2">
      {{ messageForPreview }}
    </b-card>
    <div v-else>
      <div v-if="loading">
        <i class="fas fa-cog fa-spin text-muted"></i>
        {{$t('Loading...')}}
      </div>
      <div v-else>
        <template v-if="!loading && fileInfo">
            <b-btn v-show="!isReadOnly" class="mb-2 d-print-none" variant="primary" @click="onClick(fileInfo)">
              <i class="fas fa-file-download"></i> {{$t('Download')}}
            </b-btn>
            {{fileInfo.file_name}}
        </template>
        <div v-else>
          {{$t('No files available for download')}}
        </div>
      </div>
    </div>
  </div>
</template>


<script>
  export default {
    inheritAttrs: false,
    data() {
      return {
        fileType: null,
        loading: true,
        fileInfo: null,
        requestId: null,
        collectionId: null,
        recordId: null,
        prefix: '',
      };
    },
    props: ['name', 'endpoint', 'requestFiles'],
    beforeMount() {
      this.getFileType();

      if (this.fileType == 'request') {
        this.getRequestId();
      }

      if (this.fileType == 'collection') {
        this.getCollectionInfo();
      }
    },
    mounted() {

      this.$root.$on('set-upload-data-name',
          (recordList, index, id) => this.listenRecordList(recordList, index, id));

      if (!this.fileType) {
        // Not somewhere we can download anything (like web entry start event)
        this.loading = false;
        return;
      }

      this.setPrefix();

      if (this.fileType == 'request') {
        this.getRequestFiles();
      }

      if (this.fileType == 'collection') {
        this.getCollectionFiles();
      }
    },
    computed: {
      messageForPreview() {
        return this.$t(
          'Download button for {{fileName}} will appear here.',
          { fileName: this.name }
        );
      },
      mode() {
        return this.$root.$children[0].mode;
      },
      isReadOnly() {
        return this.$attrs.readonly ? this.$attrs.readonly : false
      },
    },
    methods: {
      isInRecordList() {
        const parent =  this.$parent.$parent.$parent;
        return (parent.$options._componentTag == 'FormRecordList');
      },
      listenRecordList(recordList, index, id) {
        const parent =  this.$parent.$parent.$parent;
        if (parent === recordList) {
          if (_.has(window, 'PM4ConfigOverrides.requestFiles')) {
            const fileDataName = parent.name + '.' + this.name + (id ? '.' + id : '');
            this.fileInfo = window.PM4ConfigOverrides.requestFiles[fileDataName];
            this.loading  = false;
          }
        }
      },
      onClick() {
        if (this.fileType == 'request') {
          this.downloadRequestFile();
        }

        if (this.fileType == 'collection') {
          this.downloadCollectionFile();
        }
      },
      requestEndpoint() {
        let endpoint = this.endpoint;

        if (_.has(window, 'PM4ConfigOverrides.getFileEndpoint')) {
          endpoint = window.PM4ConfigOverrides.getFileEndpoint;
        }

        if (endpoint && this.fileInfo) {
          const query = '?name=' + encodeURIComponent(this.prefix + this.name) + '&token=' + this.fileInfo.token;
          return endpoint + query;
        }

        return "/request/" + this.requestId + "/files/" + this.fileInfo.id;
      },
      setPrefix() {
        let parent = this.$parent;
        let i = 0;
        while(!parent.loopContext) {
          parent = parent.$parent;

          if (parent === this.$root) {
            parent = null;
            break;
          }

          i++;
          if (i > 100) {
            throw "Loop Error";
          }
        }

        if (parent && parent.loopContext) {
          this.prefix = parent.loopContext + '.';
        }
      },
      downloadRequestFile() {
        ProcessMaker.apiClient({
          baseURL: "/",
          url: this.requestEndpoint(),
          method: "GET",
          responseType: "blob" // important
        }).then(response => {
          //axios needs to be told to open the file
          const url = window.URL.createObjectURL(new Blob([response.data]));
          const link = document.createElement("a");
          link.href = url;
          link.setAttribute("download", this.fileInfo.file_name);
          document.body.appendChild(link);
          link.click();
        });
      },
      downloadCollectionFile() {
        ProcessMaker.apiClient({
          url: "/files/" + this.fileInfo.id + "/contents",
          method: "GET",
          responseType: "blob" // important
        }).then(response => {
          //axios needs to be told to open the file
          const url = window.URL.createObjectURL(new Blob([response.data]));
          const link = document.createElement("a");
          link.href = url;
          link.setAttribute("download", this.fileInfo.file_name);
          document.body.appendChild(link);
          link.click();
        });
      },
      getFileType() {
        if (document.head.querySelector('meta[name="request-id"]')) {
          this.fileType = 'request';
        }

        if (document.head.querySelector('meta[name="collection-id"]')) {
          this.fileType = 'collection';
        }
      },
      getRequestId() {
        let node = document.head.querySelector('meta[name="request-id"]');
        if (node === null) {
          this.loading = false;
          return;
        }
        this.requestId = node.content;
      },
      getCollectionInfo() {
        let collectionNode = document.head.querySelector('meta[name="collection-id"]');
        if (collectionNode === null) {
          this.loading = false;
          return;
        }
        this.collectionId = collectionNode.content;

        let recordNode = document.head.querySelector('meta[name="record-id"]');
        if (recordNode === null) {
          this.loading = false;
          return;
        }
        this.recordId = recordNode.content;
      },
      getRequestFiles() {
        let requestFiles = this.requestFiles;

        if (_.has(window, 'PM4ConfigOverrides.requestFiles')) {
          requestFiles = window.PM4ConfigOverrides.requestFiles;
        }

        if (requestFiles && requestFiles[this.prefix + this.name]) {
          this.loading = false;
          this.fileInfo = requestFiles[this.prefix + this.name];
          return;
        }

        if (this.requestId === null) {
          this.loading = false;
          return;
        }
        //do not preload if the control is inside a record list becaue
        // we don't know the specific row to which the control is associated
        if (!this.isInRecordList()) {

          let endpoint = "requests/" + this.requestId + "/files?name=" + this.prefix + this.name;

          if (_.has(window, 'PM4ConfigOverrides.getFileEndpoint')) {
            endpoint = window.PM4ConfigOverrides.getFileEndpoint;
          }

          if (endpoint && this.fileInfo && this.fileInfo.token) {
            const query = '?name=' + encodeURIComponent(this.prefix + this.name) + '&token=' + this.fileInfo.token;
            return endpoint + query;
          }

          ProcessMaker.apiClient
              .get(endpoint)
              .then(response => {
                this.fileInfo = _.get(response, 'data.data.0', null);
                this.loading = false;
              });
        }
      },
      setFileInfoFromCache() {
        const info = _.get(ProcessMaker.CollectionData, this.prefix + this.name, null);
        if (info) {
          this.fileInfo = { ...info, file_name: info.name }
        }
      },
      getCollectionFiles() {
        if (this.collectionId === null || this.recordId === null) {
          this.loading = false;
          return;
        }

        let id = null;

        ProcessMaker.EventBus.$on('got-collection-data', () => {
          this.setFileInfoFromCache();
          this.loading = false;
        });

        if (!ProcessMaker.CollectionData) {
          ProcessMaker.CollectionData = {};
          ProcessMaker.apiClient
            .get("collections/" + this.collectionId + '/records/' + this.recordId)
            .then(response => {
              ProcessMaker.CollectionData = response.data.data;
              ProcessMaker.EventBus.$emit('got-collection-data');
            });
        }
      }
    }
  };
</script>

<style lang="scss" scoped>
</style>