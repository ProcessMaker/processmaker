
// Setup our login modal
window.ProcessMaker.sessionModal = function (title, message, time, warnSeconds) {
  ProcessMaker.navbar.sessionTitle = title || __("Session Warning");
  ProcessMaker.navbar.sessionMessage = message || __("Your session is about to expire.");
  ProcessMaker.navbar.sessionTime = time;
  ProcessMaker.navbar.sessionWarnSeconds = warnSeconds;
  ProcessMaker.navbar.sessionShow = true;
};

window.ProcessMaker.closeSessionModal = function () {
  ProcessMaker.navbar.sessionShow = false;
};

// Set out own specific confirm modal.
window.ProcessMaker.confirmModal = function (title, message, variant, callback, size = "md", dataTestClose = "confirm-btn-close", dataTestOk = "confirm-btn-ok") {
  ProcessMaker.navbar.confirmTitle = title || __("Confirm");
  ProcessMaker.navbar.confirmMessage = message || __("Are you sure you want to delete?");
  ProcessMaker.navbar.confirmVariant = variant;
  ProcessMaker.navbar.confirmCallback = callback;
  ProcessMaker.navbar.confirmShow = true;
  ProcessMaker.navbar.confirmSize = size;
  ProcessMaker.navbar.confirmDataTestClose = dataTestClose;
  ProcessMaker.navbar.confirmDataTestOk = dataTestOk;
};

// Set out own specific message modal.
window.ProcessMaker.messageModal = function (title, message, variant, callback) {
  ProcessMaker.navbar.messageTitle = title || __("Message");
  ProcessMaker.navbar.messageMessage = message || __("");
  ProcessMaker.navbar.messageVariant = variant;
  ProcessMaker.navbar.messageCallback = callback;
  ProcessMaker.navbar.messageShow = true;
};