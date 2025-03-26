// Store module vuex - cases
export default {};

export const cases = {
  namespaced: true,
  state: () => ({
    userConfiguration: {},
  }),
  mutations: {
    updateUserConfiguration: (state, config) => {
      state.userConfiguration = config;
    },
    updateColumnWidth: (state, { column, width }) => {
      if (!state.userConfiguration.ui_configuration) {
        state.userConfiguration.ui_configuration = {};
      }

      if (!state.userConfiguration.ui_configuration.cases) {
        state.userConfiguration.ui_configuration.cases = {};
      }

      if (!state.userConfiguration.ui_configuration.cases.columns) {
        state.userConfiguration.ui_configuration.cases.columns = {};
      }

      state.userConfiguration.ui_configuration.cases.columns[column] = {
        width,
      };
    },
  },
  getters: {
    getUserConfiguration(state) {
      return state.userConfiguration;
    },
    getCasesColumns(state) {
      return state.userConfiguration.ui_configuration?.cases?.columns;
    },
  },
};
