import Echo from "laravel-echo";
import { setGlobalVariables } from "../globalVariables";

export default () => {
  // Verify if the broadcasting is enabled
  if (Processmaker && Processmaker.broadcasting) {
    const config = Processmaker.broadcasting;
    let Pusher;

    if (config.broadcaster === "pusher") {
      Pusher = require("pusher-js");
      Pusher.logToConsole = config.debug;
    }

    setGlobalVariables({
      Echo: new Echo(config),
      Pusher,
    });
  }
};
