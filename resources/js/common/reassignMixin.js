import { getReassignUsers as getReassignUsersApi } from "../tasks/api";

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
    async getReassignUsers(filter = null) {
      try {
        const response = await getReassignUsersApi(
          filter,
          this.task?.id,
          this.task?.request_data,
          this.currentTaskUserId
        );

        this.reassignUsers = [];
        if (response?.data) {
          response.data.forEach((user) => {
            this.reassignUsers.push({
              text: user.fullname,
              value: user.id,
              active_tasks_count: user.active_tasks_count
            });
          });
        }
      } catch (error) {
        console.error('Error loading reassign users:', error);
      }
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