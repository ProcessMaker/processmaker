self.time = 0;
self.interval = null;

self.addEventListener('message', function(e) {
  if (self.hasOwnProperty(e.data.method)) {
    if (e.data.data !== undefined) {
      return self[e.data.method](e.data.data);
    } else {
      return self[e.data.method]();
    }
  }
});

self.start = function(data) {
  self.time = data.timeout * 60;
  clearInterval(self.interval);
  self.interval = setInterval(function() {
    self.time--;
    if (self.time < 30 && self.time > 0) {
      self.postMessage({method: 'countdown', data: {time: self.time}});
    }
    
    if (self.time < 1) {
      clearInterval(self.interval);
      self.postMessage({method: 'timedOut', data: {time: self.time}});
    }
  }, 1000);
}

self.stop = function() {
  clearInterval(self.interval);
}