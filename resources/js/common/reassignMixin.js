export default {
  data() {
    return {
      selectedUser: null,
      allowReassignment: false,
      reassignUsers: [],
    };
  },
  computed: {
    currentTaskUserId() {
      return this.task?.user_id ?? this.task?.user?.id;
    }
  },
  methods: {
    setAllowReassignment() {
      if (!this.task?.id) {
        return;
      }
      window.ProcessMaker.apiClient.get('tasks/user-can-reassign?tasks=' + this.task.id)
        .then((response) => {
          this.allowReassignment = response.data[this.task.id];
        });
    },
    getReassignUsers(filter = null) {
      const params = { };
      if (filter) {
        params.filter = filter;
      }
      if (this.task?.id) {
        params.assignable_for_task_id = this.task.id;
      }

      ProcessMaker.apiClient.get('users_task_count', { params }).then(response => {
        this.reassignUsers = [];
        response.data.data.forEach((user) => {
          if (this.currentTaskUserId === user.id) {
            return;
          }
          this.reassignUsers.push({
            text: user.fullname,
            value: user.id,
            active_tasks_count: user.active_tasks_count
          });
        });
      });
    },
    onReassignInput: _.debounce(function (filter) {
      this.getReassignUsers(filter);
    }, 300),

    reassignUser(redirect = false) {
      if (this.selectedUser) {
        ProcessMaker.apiClient
          .put("tasks/" + this.task.id, {
            user_id: this.selectedUser
          })
          .then(response => {
            this.$emit("on-reassign-user", this.selectedUser);
            this.showReassignment = false;
            this.selectedUser = null;
            if (redirect) {
              this.redirect('/tasks');
            }
            if (this.showPreview) {
              this.showPreview = false;
            }
          });
      }
    },
  }
}