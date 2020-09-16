<template>
    <div id="tab-form" role="tabpanel" aria-labelledby="tab-form" class="tab-pane active show h-100">
        <template v-if="taskIsOpenOrOverdue">
            <div class="card card-body border-top-0 h-100">
                <div v-if="task.component === 'task-screen'">
                    <vue-form-renderer
                        ref="renderer"
                        v-model="task.request_data" 
                        :config="task.screen.config" 
                        :computed="task.screen.computed" 
                        :custom-css="task.screen.customCss" 
                        :watchers="task.screen.watchers" 
                        @update="onUpdate" 
                        @submit="submit" 
                    />
                </div>
                <div v-else>
                    <component
                        :is="task.component"
                        :process-id="task.process_id"
                        :instance-id="task.process_request_id"
                        :token-id="task.id"
                        :screen="task.screen.config"
                        :csrf-token="csrf_token"
                        :computed="task.screen.computed"
                        :custom-css="task.screen.custom_css"
                        :watchers="task.screen.watchers"
                        :data="task.request_data"
                    >
                    </component>
                </div>
            </div>
            <div v-if="task.bpmn_tag_name === 'manualTask' || !task.screen" class="card-footer">
                <button type="button" class="btn btn-primary" @click="submit">{{ $t('Complete Task') }}</button>
            </div>
        </template>
        <template v-if="taskIsCompleted">
            <div class="card card-body border-top-0 h-100">
                <vue-form-renderer
                    ref="renderer"
                    v-if="task.allow_interstitial"
                    v-model="task.request_data" 
                    :config="task.interstitial_screen.config"
                    :computed="task.interstitial_screen.computed" 
                    :custom-css="task.interstitial_screen.customCss" 
                    :watchers="task.interstitial_screen.watchers" 
                />
                <div v-else class="card card-body text-center" v-cloak>
                    <h1>{{ $t('Task Completed') }} <i class="fas fa-clipboard-check"></i></h1>
                </div>
            </div>
        </template>
    </div>
</template>

<script>
    import { VueFormRenderer } from '@processmaker/screen-builder';

    export default {
        components: {
            VueFormRenderer
        },
        props: ['taskId', 'csrf_token', 'screen', 'data'],
        data() {
            return {
                task: null,
                redirectInProcess: false,
                disabled: false,
                socketListeners: [],
            }
        },
        watch: {
            task: {
                handler() {
                    window.ProcessMaker.nestedScreens = _.get(this.task, 'screen.nested', null);
                },
                immediate: true,
            }
        },
        computed: {
            taskIsCompleted() {
                if (!this.task) { return false; }
                return this.task.advanceStatus === 'completed' || this.task.advanceStatus === 'triggered';
            },
            taskIsOpenOrOverdue() {
                if (!this.task) { return false; }
                return this.task.advanceStatus === 'open' || this.task.advanceStatus === 'overdue';
            },
        },
        methods: {
            activityAssigned() {
                this.checkTaskStatus();
                this.redirectToNextAssignedTask(false);
            },
            reload() {
                this.loadTask(this.task.id);
            },
            loadTask(id) {
                if (this.redirectInProcess) {
                    return;
                }
                window.ProcessMaker.apiClient.get(`/tasks/${id}?include=data,user,requestor,processRequest,component,screen,requestData,bpmnTagName,interstitial,definition`)
                .then((response) => {
                    this.resetScreenState();
                    this.task = response.data;
                    // sets breadcrumbs, etc.
                    this.$emit('task-updated', response.data);
                    if (response.data.process_request.status === 'ERROR') {
                        this.hasErrors = true;
                    }
                    this.prepareTask();
                });
            },
            resetScreenState() {
                if (this.$refs.renderer && this.$refs.renderer.$children[0]) {
                    this.$refs.renderer.$children[0].currentPage = 0;
                }
            },
            redirectWhenProcessCompleted() {
                this.redirect(`/requests/${this.task.process_request_id}`);
            },
            refreshWhenProcessUpdated(data) {
                if (data.event === 'ACTIVITY_COMPLETED' || data.event === 'ACTIVITY_ACTIVATED') {
                    this.reload();
                }
            },
            checkTaskStatus(redirect=false) {
                if (this.task.status == 'COMPLETED' || this.task.status == 'CLOSED' || this.task.status == 'TRIGGERED') {
                    this.closeTask();
                }
            },
            closeTask() {
                if (this.hasErrors) {
                    this.redirect(`/requests/${this.task.process_request_id}`);
                    return;
                }
                if (!this.task.allow_interstitial) {
                    this.redirect("/tasks");
                } else {
                    this.redirectToNextAssignedTask();
                }
            },
            redirectToNextAssignedTask(redirect = false) {
                if (this.redirectInProcess) {
                    return;
                }

                if (this.task.status == 'COMPLETED' || this.task.status == 'CLOSED' || this.task.status == 'TRIGGERED') {
                    window.ProcessMaker.apiClient.get(`/tasks?user_id=${this.task.user_id}&status=ACTIVE&process_request_id=${this.task.process_request_id}`).then((response) => {
                        if (response.data.data.length > 0) {
                            const firstNextAssignedTask = response.data.data[0].id;
                            if (redirect) {
                                this.redirect(`/tasks/${firstNextAssignedTask}/edit`);
                            } else {
                                this.loadTask(firstNextAssignedTask);
                            }
                        } else if (this.task.process_request.status === 'COMPLETED') {
                            setTimeout(() => {
                                this.redirect(`/requests/${this.task.process_request_id}`);
                            }, 500);
                        }
                    });
                }
            },
            classHeaderCard (status) {
                let header = "bg-success";
                switch (status) {
                case "completed":
                    header = "bg-secondary";
                    break;
                case "overdue":
                    header = "bg-danger";
                    break;
                }
                return "card-header text-capitalize text-white " + header;
            },
            prepareTask(redirect = false) {
                this.statusCard = this.classHeaderCard(this.task.advanceStatus);
                this.checkTaskStatus(redirect);
            },
            redirect(to) {
                if (this.redirectInProcess) {
                    return;
                }
                this.redirectInProcess = true;
                window.location.href = to;
            },
            submit() {
                //single click
                if (this.disabled) {
                    return;
                }
                this.disabled = true;
                this.$emit('submit', this.task);
                console.log('HELLO EMIT');
                this.$nextTick(() => {
                    this.disabled = false;
                });
            },
            onUpdate(data) {
                ProcessMaker.EventBus.$emit('form-data-updated', data);
            },
            initSocketListeners() {
                const request_id = document.head.querySelector('meta[name="request-id"]').content;
                this.addSocketListener(`ProcessMaker.Models.ProcessRequest.${request_id}`, ".ActivityAssigned", (data) => {
                    if (data.payloadUrl) {
                        this.obtainPayload(data.payloadUrl)
                        .then(response => {
                            this.activityAssigned(response);
                        });        
                    }
                });

                this.addSocketListener(`ProcessMaker.Models.ProcessRequest.${request_id}`, ".ProcessCompleted", (data) => {
                    if (data.payloadUrl) {
                        this.obtainPayload(data.payloadUrl)
                        .then(response => {
                            this.redirectWhenProcessCompleted(response);
                        });
                    }
                });

                this.addSocketListener(`ProcessMaker.Models.ProcessRequest.${request_id}`, ".ProcessUpdated", (data) => {
                    if (data.payloadUrl) {
                        this.obtainPayload(data.payloadUrl)
                        .then(response => {
                            if (data.event) {
                            response.event = data.event;
                            }
                            this.refreshWhenProcessUpdated(response);
                        });
                    }
                });
            },
            addSocketListener(channel, event, callback) {
                this.socketListeners.push({
                    channel,
                    event
                });
                window.Echo.private(channel).listen(
                    event,
                    callback
                );
            },
            obtainPayload(url) {
                return new Promise((resolve, reject) => {
                    ProcessMaker.apiClient
                    .get(url)
                    .then(response => {
                        resolve(response.data);
                    }).catch(error => {
                        // User does not have access to the resource. Ignore.
                    });
                });
            },
        },
        mounted() {
            this.initSocketListeners();
            this.loadTask(this.taskId);
        },
        destroyed() {
            this.socketListeners.forEach((element) => {
                window.Echo.private(element.channel).stopListening(element.event);
            });
        }
    }
</script>