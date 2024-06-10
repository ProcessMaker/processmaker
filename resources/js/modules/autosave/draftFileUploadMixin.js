export default {
  watch: {
    /**
     * Set a global variable to keep track of the current task.
     * 
     * This is used by the file-upload control for saving files for drafts.
     */
    task: {
      handler() {
        window._current_task_id = this.task?.id || 0;
      },
    },
  },
  methods: {
    /**
     * When a draft is deleted, we need to reset the global request files
     * to what they are in the persisted request.
     * 
     * The response from DELETE /drafts returns the list of request files.
     */
    resetRequestFiles(response) {
      const requestFiles = response?.data?.requestFiles ?? {};
      _.set(window, 'PM4ConfigOverrides.requestFiles', requestFiles);
    },
  },
};
