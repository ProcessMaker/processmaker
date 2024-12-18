// import { setGlobalPMVariable } from "../globalVariables";

export default () => {
  const openAi = document.head.querySelector("meta[name=\"open-ai-nlq-to-pmql\"]");

  // setGlobalPMVariable("openAi", openAi ? {
  //   enabled: openAi.content,
  // } : {
  //   enabled: false,
  // });

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
