<template>
    <div>
        <div v-for="file in files.data" v-if="loaded">
            <b-btn variant="secondary" @click="onClick(file.id)">
                <i class="fas fa-download"></i> {{file.file_name}}
            </b-btn>
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
            onClick(fileId) {
                ProcessMaker.apiClient({
                    baseURL: "/",
                    url: "/request/" + this.requestId + "/files/" + fileId,
                    method: "GET",
                    responseType: "blob" // important
                }).then(response => {
                    //axios needs to be told to open the file
                    const url = window.URL.createObjectURL(new Blob([response.data]));
                    const link = document.createElement("a");
                    link.href = url;
                    link.setAttribute("download", this.files.data[0].file_name);
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