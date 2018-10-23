<template>
    <div>
        <div class="alert alert-success">Process ID: {{processId}}</div>
        <div class="alert alert-success">Using Event: {{event}}</div>
        <div class="card">
            <div class="card-body">
                <button type="submit" class="btn btn-primary" @click="submit">Request a Vacation</button>
            </div>
        </div>
    </div>

</template>

<script>
    
    export default {
        props: [
            'processId',
            'event'
        ],
        data() {
            return {
            };
        },
        mounted() {
            // Listen for our notifications
            let userId = document.head.querySelector('meta[name="user-id"]').content;
            Echo.private(`ProcessMaker.Model.User.${userId}`)
            .notification((token) => {
                ProcessMaker.pushNotification(token);
            });
        },
        methods: {
            submit() {
                ProcessMaker.apiClient.post('processes/' + this.processId + '/events/' + this.event + '/script', {
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