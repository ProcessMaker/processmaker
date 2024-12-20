export default {};

export const setGlobalVariable = (key, value) => {
  window[key] = value;
};

export const setGlobalVariables = (variables) => {
  if (typeof variables === "object") {
    Object.entries(variables).forEach(([key, value]) => {
      window[key] = value;
    });
  }
};

export const getGlobalVariable = (key) => window[key];

export const setGlobalPMVariable = (key, value) => {
  if (!window.ProcessMaker) {
    window.ProcessMaker = {};
  }

  window.ProcessMaker[key] = value;
};

export const setGlobalPMVariables = (variables) => {
  if (!window.ProcessMaker) {
    window.ProcessMaker = {};
  }

  Object.assign(window.ProcessMaker, variables);
};

export const getGlobalPMVariable = (key) => window.ProcessMaker[key];

export const setUses = (Vue, uses) => {
  if (typeof uses === "object") {
    Object.values(uses).forEach((use) => {
      if (use) {
        Vue.use(use);
      }
    });
  }
};

export const setMixins = (Vue, mixins) => {
  if (typeof mixins === "object") {
    Object.values(mixins).forEach((mixin) => {
      if (mixin) {
        Vue.mixin(mixin);
      }
    });
  }
};

export const setGlobalComponents = (Vue, components) => {
  Object.entries(components).forEach(([key, component]) => {
    if (component) {
      Vue.component(key, component);
    }
  });
};

export const loadModulesSequentially = async (modules) => {
  const loadedModules = [];

  for (const modulePath of modules) {
    try {
      const module = await modulePath;
      loadedModules.push(module);
    } catch (error) {
      console.error(`Error module: ${modulePath}`, error);
    }
  }

  return loadedModules;
};
