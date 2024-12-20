const nodeTypes = [];
nodeTypes.get = function (id) {
  return this.find((node) => node.id === id);
};

export default {
  pm: {
    nodeTypes,
  },
};
