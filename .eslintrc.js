module.exports = {
  root: true,
  env: {
    node: true,
    es6: true,
    browser: true,
  },
  globals: {
    Vue: true,
    BpmnModdle: true,
    Snap: true,
    Dispatcher: true,
    ProcessMaker: true,
  },
  extends: ["eslint:recommended", "plugin:vue/recommended", "airbnb-base"],
  parserOptions: {
    parser: "@babel/eslint-parser",
    sourceType: "module",
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
  // overrides: [
  // 	{
  // 		files: [
  // 			'**/__tests__/*.{j,t}s?(x)',
  // 			'**/tests/unit/**/*.spec.{j,t}s?(x)'
  // 		],
  // 		env: {
  // 			jest: true
  // 		}
  // 	}
  // ]
};
