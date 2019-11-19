<template>
  <div v-if="comments.length > 0" class="row px-3 mb-2 timeline">
    <template>
      <div class="px-2 py-3" v-for="value in comments">
        <avatar-image v-if="value.user" size="24" :input-data="value.user" hide-name="true"></avatar-image>
        <img v-else src="/img/systemAvatar.png" id="systemAvatar">
        <strong :title="value.updated_at">{{moment(value.updated_at).format()}}</strong>
        &nbsp;-
        {{value.body}}
      </div>
    </template>
  </div>
</template>

<script>
export default {
  props: ["commentable_id", "commentable_type", "type", "hidden"],
  data() {
    return {
      form: {
        subject: "",
        body: "",
        commentable_id: "",
        commentable_type: "",
        hidden: false,
        type: "MESSAGE"
      },
      comments: [],
      systemCommentUser: {
        id: null,
        initials: "S"
      }
    };
  },
  watch: {},
  computed: {
    disabled() {
      return this.form.subject.trim() === "" || this.form.body.trim() === "";
    }
  },
  methods: {
    emptyForm() {
      this.form.subject = "";
      this.form.body = "";
    },
    load() {
      ProcessMaker.apiClient
        .get("comments", {
          params: {
            commentable_id: this.commentable_id,
            commentable_type: this.commentable_type
          }
        })
        .then(response => {
          this.comments = response.data.data;
        });
    },
    save() {
      let that = this;
      that.form.commentable_id = that.commentable_id;
      that.form.commentable_type = that.commentable_type;
      that.form.type = that.type ? that.type : "MESSAGE";
      that.form.hidden = that.hidden ? that.hidden : false;
      ProcessMaker.apiClient.post("comments", that.form).then(response => {
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
#systemAvatar {
  max-height: 24px;
  max-width: 24px;
  border-radius: 50%;
  margin-left: 3px;
}

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

.timeline {
  background: linear-gradient(
    to right,
    transparent 0%,
    transparent calc(5% - 0.81px),
    rgb(230, 230, 230) calc(5% - 0.8px),
    rgb(230, 230, 230) calc(5% + 0.8px),
    transparent calc(5% + 0.81px),
    transparent 100%
  );
}
</style>
