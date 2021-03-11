self.time = 0;
self.interval = null;

self.addEventListener('message', function (e) {
  if (self.hasOwnProperty(e.data.method)) {
    if (e.data.data !== undefined) {
      return self[e.data.method](e.data.data);
    } else {
      return self[e.data.method]();
    }
  }
});

self.start = function (data) {
  var timestampAtStart = Math.floor(Date.now() / 1000);
  var timeoutAt = timestampAtStart + (data.timeout * 60);
  
  clearInterval(self.interval);
  self.interval = setInterval(function () {

    var currentTimestamp = Math.floor(Date.now() / 1000);
    var timeRemaining = timeoutAt - currentTimestamp;

    if (timeRemaining < data.warnSeconds && timeRemaining > 0) {
      self.postMessage({ method: 'countdown', data: { time: timeRemaining } });
    }

    if (timeRemaining < 1) {
      clearInterval(self.interval);
      self.postMessage({ method: 'timedOut', data: { time: timeRemaining } });
    }
  }, 1000);
}

self.stop = function () {
  clearInterval(self.interval);
}