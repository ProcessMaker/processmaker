export default {};

export const setGlobalVariable = (key, value) => {
  window[key] = value;
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
