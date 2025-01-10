import { setGlobalPMVariables } from "./globalVariables";

export default () => {
  const nodeTypes = [];
  nodeTypes.get = function (id) {
    return this.find((node) => node.id === id);
  };

  setGlobalPMVariables({
    nodeTypes,
  });
};
