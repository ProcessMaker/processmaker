<template>
    <div>
        <div class="alert alert-success">Process UID: {{processUid}}</div>
        <div class="alert alert-success">Process: {{processId}}</div>
        <div class="card">
            <div class="card-body">
                <button type="submit" class="btn btn-primary" @click="submit">Request a Vacation</button>
            </div>
        </div>
        <div class="alert alert-success" v-for="instance in instances">Instance created: {{instance}}</div>
        <div class="alert alert-success" v-for="token in tokens">Task created: <a v-bind:href="token.url" target="_blank">{{token.uid}}</a></div>
    </div>

</template>

<script>
    
    export default {
        props: [
            'processUid',
            'processId'
        ],
        data() {
            return {
                instances: [],
                tokens: [],
            };
        },
        mounted() {
            // Listen for our notifications
            let userId = document.head.querySelector('meta[name="user-id"]').content;
            Echo.private(`ProcessMaker.Model.User.${userId}`)
            .notification((token) => {
                this.tokens.push(token);
            });
        },
        methods: {
            submit() {
                ProcessMaker.apiClient.post('processes/' + this.processUid + '/' + this.processId + '/call', {
                    startDate: new Date().toISOString(),
                    endDate: '',
                    reason: ''
                })
                .then((response) => {
                    this.instances.push({
                        uid: response.data.instance,
                        tokens: []
                    });
                })
            }
        }

    }

</script>

<style lang="scss" scoped>

</style>