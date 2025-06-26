// eslint.config.js
import js from '@eslint/js'
import vue from 'eslint-plugin-vue'
import importPlugin from 'eslint-plugin-import'
import eslintConfigPrettier from 'eslint-config-prettier'
import eslintPluginPrettier from 'eslint-plugin-prettier'
import vueParser from 'vue-eslint-parser'
import { fileURLToPath } from 'node:url'

export default [
  {
    files: ['**/*.{js,vue}'],
    ignores: ['dist/**', 'coverage/**'],
    languageOptions: {
      parser: vueParser,
      parserOptions: {
        ecmaVersion: 'latest',
        sourceType: 'module',
      },
      // ✅ Add environment settings
      globals: {
        console: 'readonly', // or 'writable' if you modify console
      },
    },
    plugins: {
      vue,
      import: importPlugin,
      prettier: eslintPluginPrettier,
    },
    rules: {
      ...js.configs.recommended.rules,
      ...vue.configs['flat/recommended'].rules,
      ...eslintConfigPrettier.rules,
      'prettier/prettier': 'error',
      'vue/multi-word-component-names': 'off',
      'import/no-unresolved': ['error', { commonjs: false, amd: false }],
      // ✅ Optionally disable no-undef if you want to avoid defining globals
      'no-undef': 'off', // Use with caution, only if you have many globals
    },
    settings: {
      'import/resolver': {
        alias: {
          map: [['@', fileURLToPath(new URL('./src', import.meta.url))]],
          extensions: ['.js', '.ts', '.vue'],
        },
        node: {
          extensions: ['.js', '.ts', '.vue'],
        },
      },
    },
  },
]
