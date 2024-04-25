/**
 * Set a global variable to keep track of the current task.
 * 
 * This is used by the file-upload control for saving files for drafts.
 */
export default {
  watch: {
    task: {
      handler() {
        window._current_task_id = this.task?.id || 0;
      },
    },
  }
};
