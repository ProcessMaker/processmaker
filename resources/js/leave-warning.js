let isTimedOut = false;
let noUnsavedChanges = true;

if (window.ProcessMaker) {
    const {AccountTimeoutWorker, EventBus} = window.ProcessMaker;

    AccountTimeoutWorker && AccountTimeoutWorker.addEventListener("message", (event) => {
        if (event.data.method === "timedOut") {
            isTimedOut = true;
        }
    });

    EventBus.$on("save-changes", () => { noUnsavedChanges = true; });
    EventBus.$on("new-changes", () => { noUnsavedChanges = false; });
}

window.addEventListener("beforeunload", (event) => {
    if (isTimedOut || noUnsavedChanges) {
        event.preventDefault();
        return;
    }

    let confirmationMessage = __("Are you sure you want to leave?");
    event.returnValue = confirmationMessage; // Gecko, Trident, Chrome 34+

    return confirmationMessage;
});
