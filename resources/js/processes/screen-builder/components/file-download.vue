<template>
    <div>
        <div v-if="loading">
            <i class="fas fa-cog fa-spin text-muted"></i>
            {{$t('Loading...')}}
        </div>
        <div v-else>
          <template v-if="! loading && files.data && files.data.length !== 0">
              <div v-for="file in files.data">
                  <b-btn class="mb-2" variant="primary" @click="onClick(file)">
                      <i class="fas fa-file-download"></i> {{$t('Download')}}
                  </b-btn>
                  {{file.file_name}}
              </div>
          </template>
          <div v-else>
              {{$t('No files available for download')}}
          </div>
        </div>
    </div>
</template>


<script>
    export default {
        data() {
            return {
                fileType: null,
                loading: true,
                files: {},
                requestId: null,
                collectionId: null,
                recordId: null,
                listEndpoint: null,
                downloadEndpoint: null
            };
        },
        props: ['name'],
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
          if (this.fileType == 'request') {
            this.getRequestFiles();
          }
          
          if (this.fileType == 'collection') {
            this.getCollectionFiles();
          }
        },
        methods: {
            onClick(file) {
                if (this.fileType == 'request') {
                    this.downloadRequestFile(file);
                }
                
                if (this.fileType == 'collection') {
                    this.downloadCollectionFile(file);
                }
            },
            downloadRequestFile(file) {
              ProcessMaker.apiClient({
                  baseURL: "/",
                  url: "/request/" + this.requestId + "/files/" + file.id,
                  method: "GET",
                  responseType: "blob" // important
              }).then(response => {
                  //axios needs to be told to open the file
                  const url = window.URL.createObjectURL(new Blob([response.data]));
                  const link = document.createElement("a");
                  link.href = url;
                  link.setAttribute("download", file.file_name);
                  document.body.appendChild(link);
                  link.click();
              });
            },
            downloadCollectionFile(file) {
              ProcessMaker.apiClient({
                  url: "/files/" + file.id + "/contents",
                  method: "GET",
                  responseType: "blob" // important
              }).then(response => {
                  //axios needs to be told to open the file
                  const url = window.URL.createObjectURL(new Blob([response.data]));
                  const link = document.createElement("a");
                  link.href = url;
                  link.setAttribute("download", file.file_name);
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
                if (this.requestId === null) {
                    this.loading = false;
                    return;
                }
                ProcessMaker.apiClient
                    .get("requests/" + this.requestId + "/files?name=" + this.name)
                    .then(response => {
                        this.files = response.data;
                        this.loading = false;
                    });
            },
            getCollectionFiles() {
              if (this.collectionId === null || this.recordId === null) {
                  this.loading = false;
                  return;
              }
              
              let id = null;
              
              ProcessMaker.apiClient
                  .get("collections/" + this.collectionId + '/records/' + this.recordId)
                  .then(response => {
                      if (response.data.data[this.name]) {
                        let id = response.data.data[this.name].id;
                        ProcessMaker.apiClient
                            .get("files/" + id)
                            .then(response => {
                                this.files = {data: [response.data]};
                                this.loading = false;
                            });  
                      } else {
                        this.loading = false;
                        return;
                      }
                  });
              
            }
        }
    };
</script>

<style lang="scss" scoped>
</style>