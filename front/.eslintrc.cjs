module.exports = {
  root: true,
  env: {
    browser: true,
    es2021: true,
    node: true
  },
  extends: [
    'eslint:recommended',
    'plugin:vue/vue3-recommended'
  ],
  parserOptions: {
    ecmaVersion: 'latest',
    sourceType: 'module'
  },
  plugins: ['vue', 'import'],
  rules: {
    // Reglas personalizadas
    'import/no-unresolved': ['error', { caseSensitive: true }],
    'vue/multi-word-component-names': 'off'
  },
  settings: {
    'import/resolver': {
      node: {
        extensions: ['.js', '.ts', '.vue']
      },
      alias: {
        map: [['@', './src']],
        extensions: ['.js', '.ts', '.vue']
      }
    }
  }
}
