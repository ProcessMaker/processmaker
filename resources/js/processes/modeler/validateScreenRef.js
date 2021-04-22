const validateScreenRef = () => ProcessMaker.EventBus.$on('modeler-validate', (node, reporter) => {
  if (!['bpmn:Task', 'bpmn:ManualTask'].includes(node.$type)) {
    return;
  }

  if (node.screenRef) {
    return;
  }

  reporter.report(node.id, 'Please select an input screen');
});

export default validateScreenRef;
