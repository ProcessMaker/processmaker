// import { setGlobalPMVariable } from "../globalVariables";

export default () => {
  const openAi = document.head.querySelector("meta[name=\"open-ai-nlq-to-pmql\"]");

  return {
    pm: {
      openAi: openAi ? {
        enabled: openAi.content,
      } : {
        enabled: false,
      },
    },
  };
};
