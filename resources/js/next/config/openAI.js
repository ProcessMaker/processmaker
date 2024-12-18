import { setGlobalPMVariable } from "../globalVariables";

const openAi = document.head.querySelector("meta[name=\"open-ai-nlq-to-pmql\"]");

setGlobalPMVariable("openAi", openAi ? {
  enabled: openAi.content,
} : {
  enabled: false,
});
