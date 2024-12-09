window.Modeler = require("@processmaker/modeler");

window.ProcessMaker.nodeTypes = [];
window.ProcessMaker.nodeTypes.get = function (id) {
  return this.find((node) => node.id === id);
};
