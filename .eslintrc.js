module.exports = {
  root: true,
  env: {
    node: true,
    browser: true,
  },
  globals: {
    Vue: true,
    BpmnModdle: true,
    Snap: true,
    Dispatcher: true,
    ProcessMaker: true,
    _: true,
  },
  extends: ["eslint:recommended", "plugin:vue/recommended", "airbnb-base"],
  parserOptions: {
    parser: "@babel/eslint-parser",
    sourceType: "module",
    ecmaVersion: 2020,
    babelOptions: {
      configFile: "./babel.config.json",
    },
  },
  plugins: [
    "vue",
  ],
  rules: {
    quotes: ["error", "double"],
    "max-len": ["error", {
      code: 140,
      ignoreComments: true,
    }],
  },
  overrides: [
    {
      files: [
        "tests/**/*.js",
      ],
      plugins: ["jest"],
      extends: ["plugin:jest/recommended"],
    },
  ],
};
