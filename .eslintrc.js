module.exports = {
	root: true,
	env: {
		node: true,
		es6: true,
		browser: true
	},
	globals: {
		Vue: true,
		BpmnModdle: true,
		Snap: true,
		Dispatcher: true
	},
	extends: ['plugin:vue/base', 'eslint:recommended', 'airbnb', 'plugin:flowtype/recommended', 'standard'],
	parserOptions: {
		parser: 'babel-eslint',
		ecmaVersion: 2017,
		ecmaFeatures: {
			experimentalObjectRestSpread: true
		},
		sourceType: 'module'
	},
	plugins: [
		'flowtype',
		'standard'
	],
	rules: {
		'comma-dangle': ['error', 'always-multiline'],
		indent: [
			1,
			2,
			{
				'SwitchCase': 1
			}
		],
		'linebreak-style': [
			'error',
			'unix'
		],
		quotes: [
			2,
			'double'
		],
		'semi': [
			1,
			'always'
		],
		'max-len': [
			1,
			{
				'code': 120,
				'comments': 80,
				'ignoreTrailingComments': true,
				'ignoreUrls': true,
				'ignoreTemplateLiterals': false,
				'ignoreRegExpLiterals': true
			}
		],
		'one-var': [
			1,
			'consecutive'
		],
		'prefer-const': [
			0
		],
		'prefer-destructuring': 1,
		'object-curly-spacing': [
			1,
			'never'
		],
		'no-underscore-dangle': [
			1,
			{
				'allowAfterThis': true
			}
		],
		'no-use-before-define': [
			1,
			{
				'functions': true,
				'classes': true
			}
		],
		'no-param-reassign': 0,
		'no-cond-assign': [
			'error',
			'always'
		],
		'no-multi-assign': [
			'error'
		],
		'no-useless-return': 0,
		'class-methods-use-this': 0,
		'generator-star-spacing': [
			'error',
			{
				'before': false,
				'after': true
			}
		],
		'array-callback-return': [
			'error',
			{
				'allowImplicit': true
			}
		]
	},
	overrides: [
		{
			files: [
				'**/__tests__/*.{j,t}s?(x)',
				'**/tests/unit/**/*.spec.{j,t}s?(x)'
			],
			env: {
				jest: true
			}
		}
	]
};
