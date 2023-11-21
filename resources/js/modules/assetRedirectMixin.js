export default {
  computed: {
    isProjectRoute() {
      return window.location.pathname.startsWith("/designer/projects/");
    },
  },
  methods: {
    appendProjectIdToURL(url, projectId) {
      if (this.isProjectRoute) {
        url.searchParams.append("project_id", projectId);
      }
    },
  },
};
