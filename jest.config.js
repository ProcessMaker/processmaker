module.exports = {
  setupFiles: [
    "<rootDir>/jest/globals.js"
  ],
  testURL: "http://localhost",
  moduleFileExtensions: [
    "vue",
    "json",
    "js"
  ],
  transform: {
    ".*\\.(js)$": "babel-jest",
    ".*\\.(vue)$": "@vue/vue2-jest"
  },
  moduleNameMapper: {
    "@pmjs(.*)$": "<rootDir>/resources/js/$1"
  },
  transformIgnorePatterns: [
    "node_modules/(?!(vuetable-2|vue-uniq-ids)/)"
  ],
  roots: [
    "<rootDir>/resources/js/",
    "<rootDir>/tests/js/"
  ],
  collectCoverage: true,
  coverageDirectory: "<rootDir>/tests/js/coverage"
}