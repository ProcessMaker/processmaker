<template>
  <div class="px-4 mb-2 timeline">
    <template v-for="(item,index) in comments">
      <component 
        v-bind:is="component(item)"
        v-bind:key="`timeline-item-${index}`"
        v-bind:value="item"
        v-bind:icon="icon(item)"
        :allow-reactions="reactions"
        :allow-edit="edit"
        :allow-remove="remove"
        :allow-voting="voting"
        :read-only="readonly"
        @refresh="load"
      />
    </template>
    <template v-if="isDefined('comment-editor') && adding">
    <comment-editor
      v-model="newComment"
      class="mt-2"
      v-bind:commentable_id="commentable_id"
      v-bind:commentable_type="commentable_type"
      @refresh="load"
    />
    </template>
  </div>
</template>

<script>
const SubjectIcons = {
  'Task Complete': 'far fa-square',
  'Gateway': 'far fa-square fa-rotate-45',
};
export default {
  props: ["commentable_id", 
            "commentable_type", 
            "type", 
            "hidden", 
            "reactions", 
            "voting", 
            "edit", 
            "remove",
            "adding",
            "readonly"
        ],
  data() {
    return {
      newComment: '',
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
    isDefined(component) {
      return component in Vue.options.components;
    },
    component(item) {
      const component = `timeline-${item.type.toLowerCase()}`;
      return component in Vue.options.components ? component : 'timeline-item';
    },
    icon(item) {
      return SubjectIcons[item.subject] || '';
    },
    emptyForm() {
      this.form.subject = "";
      this.form.body = "";
    },
    load() {
      this.comments = [];
      ProcessMaker.apiClient
        .get("comments", {
          params: {
            commentable_id: this.commentable_id,
            commentable_type: this.commentable_type,
            includes: 'children',
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
    transparent 38px,
    rgb(230, 230, 230) 38px,
    rgb(230, 230, 230) 40px,
    transparent 40px,
    transparent 100%
  );
}

.timeline-badge {
  width: 28px;
  height: 24px;
  margin-left:0px;
  padding: 0;
}
.timeline-icon {
  background-color: rgb(225, 228, 232);
  color: #788793;
}
.fa-rotate-45 {
  -moz-transform: rotate(45deg);
  -webkit-transform: rotate(45deg);
  -o-transform: rotate(45deg);
  -ms-transform: rotate(45deg);
  transform: rotate(45deg);
}
</style>
