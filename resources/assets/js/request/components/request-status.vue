<template>
    <div class="card">
        <div class="card-body">
            <h1>{{process ? process.name : 'Loading'}}</h1>
            <p v-if="process">
                {{process.description}}
            </p>
            <div class="row" v-if="process">
                <div class="col-sm-6 col-md-3">
                    <p class="font-weight-bold text-right">Created by:</p>
                </div>
                <div class="col-sm-6 col-md-3">
                    {{process.user}}
                </div>

                <div class="col-sm-6 col-md-3">
                    <p class="font-weight-bold text-right">Created:</p>
                </div>
                <div class="col-sm-6 col-md-3">
                    {{formatDate(process.created_at)}}
                </div>

                <div class="col-sm-6 col-md-3">
                    <p class="font-weight-bold text-right">Category:</p>
                </div>
                <div class="col-sm-6 col-md-3">
                    {{process.category}}
                </div>

                <div class="col-sm-6 col-md-3">
                    <p class="font-weight-bold text-right">Last Updated:</p>
                </div>
                <div class="col-sm-6 col-md-3">
                    {{formatDate(process.updated_at)}}
                </div>
            </div>

            <h2>Current Tasks</h2>
            <hr>
            <div :key="index" v-for="(delegation, index) in current">
                <div class="row">
                    <div class="col-md-4">
                        <span class="button-space">
                            <a class="btn-primary btn-sm" v-bind:href="openLink(process, delegation)" target="_blank">
                                Open
                            </a>
                        </span>
                        <strong>{{delegation.definition.name}}</strong>
                    </div>
                    <div class="col-md">
                        {{delegation.user.firstname}} {{delegation.user.lastname}}
                    </div>
                    <div class="col-md">
                        {{formatDate(delegation.delegate_date)}}
                    </div>
                </div>
                <hr>
            </div>

            <h2>Completed Tasks</h2>
            <hr>
            <div :key="index" v-for="(delegation, index) in completed">
                <div class="row">
                    <div class="col-md-4">
                        <span class="button-space">
                            <a class="btn-success btn-sm" v-bind:href="openLink(process, delegation)" target="_blank">
                                View
                            </a>
                        </span>
                        <strong>{{delegation.definition.name}}</strong>
                    </div>
                    <div class="col-md">
                        {{delegation.user.firstname}} {{delegation.user.lastname}}
                    </div>
                    <div class="col-md">
                        {{formatDate(delegation.delegate_date)}}
                    </div>
                </div>
                <hr>
            </div>
        </div>
    </div>
</template>

<script>
    import moment from "moment"

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
            ProcessMaker.apiClient.get(`processes/${this.processUid}`)
                    .then((response) => {
                        this.process = response.data;
                        this.update();
                    })
        },
        methods: {
            formatDate(isoDate) {
                return moment(isoDate).format('YYYY-MM-DD hh:mm');
            },
            openLink(process, delegation) {
                return '/tasks/' +
                        delegation.definition.id +
                        '/' +
                        process.uid +
                        '/' +
                        delegation.application.uid +
                        '/' +
                        delegation.uid;
            },
            update() {
                // Get first the current delegations
                ProcessMaker.apiClient.get(`processes/${this.processUid}/instances/${this.instanceUid}/tokens`, {
                    params: {
                        include: 'user',
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
    .button-space {
        display: inline-block;
        width: 5em;
        text-align: center;
    }
</style>


