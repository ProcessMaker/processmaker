import Echo from "laravel-echo";

if (window.Processmaker && window.Processmaker.broadcasting) {
  const config = window.Processmaker.broadcasting;

  if (config.broadcaster == "pusher") {
    window.Pusher = require("pusher-js");
    window.Pusher.logToConsole = config.debug;
  }

  window.Echo = new Echo(config);
}
