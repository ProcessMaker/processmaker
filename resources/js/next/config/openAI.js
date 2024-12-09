const openAiEnabled = document.head.querySelector("meta[name=\"open-ai-nlq-to-pmql\"]");

if (openAiEnabled) {
  window.ProcessMaker.openAi = {
    enabled: openAiEnabled.content,
  };
} else {
  window.ProcessMaker.openAi = {
    enabled: false,
  };
}
