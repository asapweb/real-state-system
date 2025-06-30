// eslint.config.js
import js from '@eslint/js'
import vue from 'eslint-plugin-vue'
import importPlugin from 'eslint-plugin-import'
import eslintConfigPrettier from 'eslint-config-prettier'
import eslintPluginPrettier from 'eslint-plugin-prettier'
import vueParser from 'vue-eslint-parser'
import { fileURLToPath } from 'node:url'
import path from 'node:path'

const __dirname = path.dirname(fileURLToPath(import.meta.url))

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
      globals: {
        console: 'readonly',
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
      'no-undef': 'off',
    },
    settings: {
      'import/resolver': {
        alias: {
          map: [['@', path.resolve(__dirname, 'src')]],
          extensions: ['.js', '.ts', '.vue'],
        },
        node: {
          extensions: ['.js', '.ts', '.vue'],
        },
      },
    },
  },
]
