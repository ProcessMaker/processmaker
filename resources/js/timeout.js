self.time = 0;
self.interval = null;
self.restartWorker = false;

self.addEventListener("message", (e) => {
  if (self.hasOwnProperty(e.data.method)) {
    if (e.data.data !== undefined) {
      return self[e.data.method](e.data.data);
    }
    return self[e.data.method]();
  }
});

self.start = function (data) {
  // Exit if timeout control is not enabled
  if (!data.enabled) {
    return;
  }
  const timestampAtStart = Math.floor(Date.now() / 1000);
  const timeoutAt = timestampAtStart + (data.timeout * 60);

  self.restartWorker = false;

  clearInterval(self.interval);
  self.interval = setInterval(() => {
    const currentTimestamp = Math.floor(Date.now() / 1000);
    const timeRemaining = timeoutAt - currentTimestamp;

    if (timeRemaining < data.warnSeconds && timeRemaining > 0) {
      self.postMessage({ method: "countdown", data: { time: timeRemaining } });
    }

    if (timeRemaining > data.warnSeconds && self.restartWorker) {
      clearInterval(self.interval);
      self.start(data);
      self.restartWorker = false;
      return;
    }

    if (timeRemaining < 1) {
      clearInterval(self.interval);
      self.postMessage({ method: "timedOut", data: { time: timeRemaining } });
    }
  }, 1000);
};

self.stop = function () {
  self.restartWorker = false;
  clearInterval(self.interval);
};

self.restart = function (data) {
  self.restartWorker = true;
};
