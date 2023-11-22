export default {
  data() {
    return {
      redirectUrl: null,
    };
  },
  mounted() {
    this.setRedirectUrl();
    ProcessMaker.EventBus.$on("redirect", () => {
      this.handleRedirection();
    });
  },
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
    setRedirectUrl() {
      const queryParams = new URLSearchParams(window.location.search);
      const projectId = queryParams.get("project_id");

      if (projectId) {
        this.redirectUrl = `/designer/projects/${projectId}`;
      }
    },
    handleRedirection() {
      return new Promise((resolve) => {
        if (this.redirectUrl) {
          window.location.href = this.redirectUrl;
          resolve(true);
        }
        resolve(false);
      });
    },
  },
};
