<template>
    <div>
        <button type="submit" class="btn btn-primary" @click="submit">Request a Vacation</button>
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
                ProcessMaker.apiClient.post('processes/' + this.processUid + '/' + this.processId + '/call', {
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