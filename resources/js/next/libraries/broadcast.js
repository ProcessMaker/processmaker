import Echo from "laravel-echo";

export default (globalInput) => {
  // Verify if the broadcasting is enabled
  if (Processmaker && Processmaker.broadcasting) {
    const config = Processmaker.broadcasting;
    let Pusher;

    if (config.broadcaster === "pusher") {
      Pusher = require("pusher-js");
      Pusher.logToConsole = config.debug;

      setGlobalVariable("Pusher", Pusher);
    }

    return {
      global: {
        Echo: new Echo(config),
        Pusher,
      },
    };
  }

  return {
    global: {},
  };
};
