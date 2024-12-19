const Modeler = require("@processmaker/modeler");

const nodeTypes = [];
nodeTypes.get = function (id) {
  return this.find((node) => node.id === id);
};

export default {
  global: {
    Modeler,
  },
  pm: {
    nodeTypes,
  },
};

// setGlobalPMVariables({
//   nodeTypes,
// });

// console.log("Modeler 2222", Modeler);

// setGlobalVariable("Modeler", Modeler);
