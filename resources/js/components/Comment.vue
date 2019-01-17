<template>
    <span v-if="comments.length > 0">
        <h5>Comments</h5>
        <template v-for="value in comments">
        <div class="card border-0">
            <div class="card-body p-0 ml-2 d-inline-flex float-left align-items-center">
                <avatar-image size="35" :input-data="value.user" hide-name="true"></avatar-image>
                <p class="card-text ml-3">
                    {{moment(value.updated_at).format()}}
                    <span style="font-weight: bold">{{value.user.username}}</span>
                    {{value.body}}
                </p>
            </div>
        </div>
        <hr>
        </template>
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
                comments: []
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
