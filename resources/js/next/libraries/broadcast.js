import TenantAwareEcho from "../../common/TenantAwareEcho";
import { setGlobalVariables } from "../globalVariables";
import PusherJs from "pusher-js";

export default () => {
  // Verify if the broadcasting is enabled
  if (Processmaker && Processmaker.broadcasting) {
    const config = Processmaker.broadcasting;
    let Pusher;

    if (config.broadcaster === "pusher") {
      Pusher = PusherJs;
      Pusher.logToConsole = config.debug;
    }

    setGlobalVariables({
      Echo: new TenantAwareEcho(config),
      Pusher,
    });
  }
};
