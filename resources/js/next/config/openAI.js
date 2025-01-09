import { setGlobalPMVariables } from "../globalVariables";

export default () => {
  const openAi = document.head.querySelector("meta[name=\"open-ai-nlq-to-pmql\"]");

  setGlobalPMVariables({
    openAi: openAi ? {
      enabled: openAi.content,
    } : {
      enabled: false,
    },
  });
};
