import { showLeaveWarning } from "../../leave-warning";

export default {
  methods: {
    onClose() {
      window.removeEventListener("beforeunload", showLeaveWarning);
      const href = this.closeHref || "/";
      const forceAutosave = true;
      this.handleAutosave(forceAutosave)
        .then(() => {
          window.location.href = href;
        })
        .catch(() => {
          window.addEventListener("beforeunload", showLeaveWarning);
        });
    },
  },
};
