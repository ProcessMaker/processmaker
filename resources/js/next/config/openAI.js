import { setGlobalPMVariable } from "../globalVariables";

const openAiEnabled = document.head.querySelector("meta[name=\"open-ai-nlq-to-pmql\"]");

setGlobalPMVariable("openAiEnabled", openAiEnabled ? {
  enabled: openAiEnabled.content,
} : {
  enabled: false,
});
