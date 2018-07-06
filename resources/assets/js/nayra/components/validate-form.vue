<template>
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Approve</h5>
            <form>
                <legend>Request</legend>
                <div class="form-group">
                    <label for="startDate">Start date</label>
                    <input disabled id ="startDate" aria-describedby="startDateHelp" type="datetime" class="form-control" v-model="startDate" placeholder="Start Date">
                </div>
                <div class="form-group">
                    <label for="endDate">End date</label>
                    <input disabled id ="endDate" aria-describedby="endDateHelp" type="datetime" class="form-control" v-model="endDate" placeholder="End Date">
                </div>
                <div class="form-group">
                    <label for="endDate">Reason</label>
                    <textarea disabled id ="reason" class="form-control" v-model="reason" placeholder="Reason" rows="3"></textarea>
                </div>
                <legend>Approved?</legend>
                <div class="form-group">
                    <i class="fa fa-check"></i>
                </div>
                <legend>Human Resources</legend>
                <button type="button" class="btn btn-primary" @click="submit">Complete</button>
            </form>
        </div>
    </div>
</template>

<script>

    export default {
        props: [
            'processUid',
            'instanceUid',
            'tokenUid',

            'startDate',
            'endDate',
            'reason',
        ],
        data() {
            return {
                approved: '0',
                tokens: [],
            };
        },
        mounted() {
            // Listen for our notifications
            let userId = document.head.querySelector('meta[name="user-id"]').content;
            Echo.private(`ProcessMaker.Model.User.${userId}`)
                .notification((token) => {
                    this.$parent.messages.push(token);
                });
        },
        methods: {
            submit() {
                ProcessMaker.apiClient.post(
                        'processes/' + this.processUid +
                        '/instances/' + this.instanceUid +
                        '/tokens/' + this.tokenUid +
                        '/complete', 
                {
                })
                .then((response) => {
                })
            }
        }

    }

</script>

<style lang="scss" scoped>

</style>