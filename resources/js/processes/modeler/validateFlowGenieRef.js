const validateFlowGenieRef = () => ProcessMaker.EventBus.$on("modeler-validate", (node, reporter) => {
  if (node.$type !== "bpmn:ServiceTask" || node.implementation !== "package-ai/processmaker-ai-task") {
    return;
  }

  if (node.config) {
    const config = JSON.parse(node.config);
    if (config && config.flow_genie_id) {
      return;
    }
  }

  reporter.report(node.id, "Please select a FlowGenie");
});

export default validateFlowGenieRef;
