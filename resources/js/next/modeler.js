import { setGlobalPMVariables } from "./globalVariables";

const nodeTypes = [];
nodeTypes.get = function (id) {
  return this.find((node) => node.id === id);
};

setGlobalPMVariables({
  nodeTypes,
});
