<template>
    <div>
        <div class="alert alert-success">Process UID: {{processUid}}</div>
        <div class="alert alert-success">Using Event: {{event}}</div>
        <div class="card">
            <div class="card-body">
                <button type="submit" class="btn btn-primary" @click="submit">Request a Vacation</button>
            </div>
        </div>
        <div class="alert alert-success" v-for="token in tokens">Task created: <a v-bind:href="token.url" target="_blank">{{token.uid}}</a></div>
    </div>

</template>

<script>
    
    export default {
        props: [
            'processUid',
            'event'
        ],
        data() {
            return {
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
                ProcessMaker.apiClient.post('processes/' + this.processUid + '/events/' + this.event + '/trigger', {
                    startDate: new Date().toISOString(),
                    endDate: '',
                    reason: '',
                    approved: false 
                })
                .then((response) => {
                })
            }
        }

    }

</script>

<style lang="scss" scoped>

</style>