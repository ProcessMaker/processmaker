<template>
    <span>
        <div class="">
            <h5>Comments</h5>
            <div class="timeline-background">
                <template v-for="value in comments">
                    <div class="row align-items-center pb-2">
                        <div class="col-auto d-flex align-items-center pr-1">
                            <avatar-image v-if="value.user" size="35" :input-data="value.user" hide-name="true"></avatar-image>
                            <avatar-image v-else size="35" :input-data="systemCommentUser" hide-name="true"></avatar-image>
                        </div>
                        <div class="col pl-0">
                            <div class="row">
                                <div class="col date">
                                    {{moment(value.updated_at).format()}}
                                </div>
                            </div>
                            <div class="row">
                                <div class="col comment-body">
                                    {{value.body}}
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </span>
</template>

<script>
    export default {
        props: ['commentable_id', 'commentable_type', 'type', 'hidden'],
        data() {
            return {
                form: {
                    subject: '',
                    body: '',
                    commentable_id: '',
                    commentable_type: '',
                    hidden: false,
                    type: 'MESSAGE'
                },
                comments: [],
                systemCommentUser: {
                    id: null,
                    initials: 'S',
                }
            }
        },
        watch: {},
        computed: {
            disabled() {
                return this.form.subject.trim() === '' || this.form.body.trim() === '';
            },
        },
        methods: {
            emptyForm() {
                this.form.subject = '';
                this.form.body = '';
            },
            load() {
                ProcessMaker.apiClient.get("comments", {
                    params: {
                        commentable_id: this.commentable_id,
                        commentable_type: this.commentable_type,
                    }
                }).then(response => {
                    console.log(response);
                    this.comments = response.data.data;
                });
            },
            save() {
                let that = this;
                that.form.commentable_id = that.commentable_id;
                that.form.commentable_type = that.commentable_type;
                that.form.type = that.type ? that.type : 'MESSAGE';
                that.form.hidden = that.hidden ? that.hidden : false;
                ProcessMaker.apiClient
                    .post("comments", that.form)
                    .then(response => {
                        that.load();
                        that.emptyForm();
                    });
            }
        },
        mounted() {
            this.load();
        }
    };
</script>

<style>
.row .col-auto {
    height: 3em;
}

.date {
    color: #848484;
    font-size: 0.8em;
}

.comment-body {
    line-height: 1.2em;
}
</style>
