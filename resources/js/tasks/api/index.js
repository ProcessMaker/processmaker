import { getApi } from "../variables/index";

// getReassignUsers(filter = null) {
//   const params = { };
//   if (filter) {
//     params.filter = filter;
//   }
//   if (this.task?.id) {
//     params.assignable_for_task_id = this.task.id;
//   }

//   ProcessMaker.apiClient.get('users_task_count', { params }).then(response => {
//     this.reassignUsers = [];
//     response.data.data.forEach((user) => {
//       if (this.currentTaskUserId === user.id) {
//         return;
//       }
//       this.reassignUsers.push({
//         text: user.fullname,
//         value: user.id,
//         active_tasks_count: user.active_tasks_count
//       });
//     });
//   });
// }

export const getReassignUsers = async (filter = null, taskId = null) => {
  const api = getApi();
  console.log("getReassignUsers", filter, taskId);
  const response = await api.get("users_task_count", { params: { filter, assignable_for_task_id: taskId } });
  return response.data;
};

export const updateReassignUser = async (taskId, userId) => {
  const api = getApi();
  const response = await api.put(`tasks/${taskId}`, { user_id: userId });
  return response.data;
};

// const reassignUser = async (redirect = false) => {
//   if (selectedUser.value) {
//     ProcessMaker.apiClient
//       .put(`tasks/${this.task.id}`, {
//         user_id: this.selectedUser,
//       })
//       .then((response) => {
//         this.$emit("on-reassign-user", this.selectedUser);
//         this.showReassignment = false;
//         this.selectedUser = null;
//         if (redirect) {
//           this.redirect("/tasks");
//         }
//         if (this.showPreview) {
//           this.showPreview = false;
//         }
//       });
//   }
// };
