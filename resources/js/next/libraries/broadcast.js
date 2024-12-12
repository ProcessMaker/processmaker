import Echo from "laravel-echo";
import { setGlobalVariable, setGlobalPMVariable } from "../globalVariables";

// Verify if the broadcasting is enabled
if (window.Processmaker && window.Processmaker.broadcasting) {
  const config = window.Processmaker.broadcasting;

  if (config.broadcaster === "pusher") {
    const Pusher = require("pusher-js");
    Pusher.logToConsole = config.debug;

    setGlobalVariable("Pusher", Pusher);
  }

  window.Echo = new Echo(config);
  console.log("ECHOOOOOOOOOO ---- last");
}
