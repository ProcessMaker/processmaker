<template>
    <div>
        <template  v-if="loaded && files.data && files.data.length !== 0">
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
        props: ['name'],
        beforeMount() {
            this.getRequestId();
        },
        mounted() {
            this.getFiles();
        },
        methods: {
            onClick(file) {
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
            getRequestId() {
                let node = document.head.querySelector('meta[name="request-id"]');
                if (node === null) {
                    return;
                }
                this.requestId = node.content;
            },
            getFiles() {
                if (this.requestId === null) {
                    return;
                }
                ProcessMaker.apiClient
                    .get("requests/" + this.requestId + "/files?name=" + this.name)
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