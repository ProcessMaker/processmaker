let isTimedOut = false;
let noUnsavedChanges = true;

if (window.ProcessMaker) {
  const { AccountTimeoutWorker, EventBus } = window.ProcessMaker;

  AccountTimeoutWorker && AccountTimeoutWorker.addEventListener("message", (event) => {
    if (event.data.method === "countdown" && event.data.data.time < 3) {
      isTimedOut = true;
    }
  });

  EventBus.$on("save-changes", () => { noUnsavedChanges = true; });
  EventBus.$on("new-changes", () => { noUnsavedChanges = false; });
}

const showLeaveWarning = (event) => {
  if (isTimedOut || noUnsavedChanges) {
    return;
  }

  event.preventDefault();
  const confirmationMessage = __("Are you sure you want to leave?");
  event.returnValue = confirmationMessage; // Gecko, Trident, Chrome 34+
};

window.addEventListener("beforeunload", showLeaveWarning);

export { showLeaveWarning };
