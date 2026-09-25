import Pusher from "pusher-js";
import Echo from "laravel-echo";
import ScriptExecutors from "./ScriptExecutors.vue";
import { setGlobalVariables } from "../../next/globalVariables";

if (Processmaker && Processmaker.script_microservice && Processmaker.script_microservice.enabled) {
  const config = Processmaker.script_microservice.broadcasting;

  setGlobalVariables({
    ScriptMicroserviceEcho: new Echo({
      ...config,
      client: new Pusher(config.key, config),
    }),
  });
}

new Vue({
  el: "#script-executors",
  components: { ScriptExecutors },
});
