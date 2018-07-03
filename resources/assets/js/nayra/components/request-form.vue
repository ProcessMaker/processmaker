<template>
    <div>
        <div class="alert alert-success">Process UID: {{processUid}}</div>
        <div class="alert alert-success">Using Event: {{event}}</div>
        <div class="card">
            <div class="card-body">
                <button type="submit" class="btn btn-primary" @click="submit">Request a Vacation</button>
            </div>
        </div>
        <div class="alert alert-success" v-for="instance in instances">Instance created: {{instance}}</div>
    </div>

</template>

<script>
    
    export default {
        props: [
            'processUid',
            'event'
        ],
        data() {
            return {instances: []};
        },
        mounted() {
            // Listen for our notifications
            let userId = document.head.querySelector('meta[name="user-id"]').content;
            Echo.private(`ProcessMaker.Model.User.${userId}`)
            .notification((notification) => {
                    this.instances.push(notification);
            });
        },
        methods: {
            submit() {
                ProcessMaker.apiClient.post('processes/' + this.processUid + '/events/' + this.event + '/trigger', {
                    start: new Date().toISOString(),
                    end: '',
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