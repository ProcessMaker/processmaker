<template>
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Request</h5>
            <form>
                <input type="hidden" v-model="processUid" placeholder="Process UID">
                <div class="form-group">
                    <label for="startDate">Start date</label>
                    <input id ="startDate" aria-describedby="startDateHelp" type="datetime" class="form-control" v-model="startDate" placeholder="Start Date">
                </div>
                <div class="form-group">
                    <label for="endDate">End date</label>
                    <input id ="endDate" aria-describedby="endDateHelp" type="datetime-local" class="form-control" v-model="endDate" placeholder="End Date">
                </div>
                <div class="form-group">
                    <label for="endDate">Reason</label>
                    <textarea id ="reason" class="form-control" v-model="reason" placeholder="Reason" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary" @click="submit">Continue</button>
            </form>
        </div>
    </div>
</template>

<script>
    
    export default {
        props: [
            'startDate',
            'endDate',
            'reason',
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