// Store module vuex - cases
export const cases = {
  namespaced: true,
  state: () => ({
    userConfiguration: {},
  }),
  mutations: {
    updateUserConfiguration: (state, config) => {
      state.userConfiguration = config;
    },
    updateCollapseContainer: (state, value) => {
      state.userConfiguration.ui_configuration.cases.isMenuCollapse = value;
    },
  },
  getters: {
    getUserConfiguration(state) {
      return state.userConfiguration;
    },
    getCollapseContainer(state) {
      return state.userConfiguration?.ui_configuration?.cases?.isMenuCollapse;
    },
  },
};
