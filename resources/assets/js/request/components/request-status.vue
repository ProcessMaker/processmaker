<template>
<div>
    <h1>{{process ? process.name : 'Loading'}}</h1>
    <p v-if="process">
    {{process.description}}
    </p>
    <strong>Case:</strong> {{instanceUid}}

    <h2>Current Tasks</h2>
    <div class="card" :key="index" v-for="(delegation, index) in current">
        Delegation Uid: {{delegation.uid}}<br>
        Created: {{delegation.delegate_date}}<br>
        Assigned To: {{delegation.user_id}}<br>
    </div>

    <h2>Completed Tasks</h2>
    <div class="card" :key="index" v-for="(delegation, index) in completed">
        Delegation Uid: {{delegation.uid}}<br>
        Created: {{delegation.delegate_date}}<br>
        Assigned To: {{delegation.user_id}}<br>
    </div>


</div>
</template>

<script>
export default {
    props: [
        'processUid',
        'instanceUid'
    ],
    data() {
        return {
            process: null,
            instance: null,
            current: [],
            completed: []
        }
    },
    mounted() {
        console.log("HUZZAH")
        ProcessMaker.apiClient.get(`processes/${this.processUid}`)
            .then((response) => {
                this.process = response.data;
                this.update();
            })
    },
    methods: {
        update() {
            // Get first the current delegations
            ProcessMaker.apiClient.get(`processes/${this.processUid}/instances/${this.instanceUid}/tokens`, {
                params: {
                    thread_status: 'ACTIVE'
                }
            })
            .then((response) => {
                this.current = response.data.data;
            });

            // Now get completed delegations
            ProcessMaker.apiClient.get(`processes/${this.processUid}/instances/${this.instanceUid}/tokens`, {
                params: {
                    thread_status: 'CLOSED'
                }
            })
            .then((response) => {
                this.completed = response.data.data;
            });


        }
    }
}
</script>

<style lang="scss" scoped>

</style>


