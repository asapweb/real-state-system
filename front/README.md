# real-state-front

This template should help get you started developing with Vue 3 in Vite.

## Deploy

```sh
npm run build
# Eliminar el contenido anterior
ssh ariel@intra.dermatologiacial.com.ar "rm -rf /var/www/cassa/front/dist/*"
# Copiar los nuevos archivos
scp -r dist/* ariel@intra.dermatologiacial.com.ar:/var/www/cassa/front/dist
```

## Recommended IDE Setup

[VSCode](https://code.visualstudio.com/) + [Volar](https://marketplace.visualstudio.com/items?itemName=Vue.volar) (and disable Vetur).

## Customize configuration

See [Vite Configuration Reference](https://vite.dev/config/).

## Project Setup

```sh
npm install
```

### Compile and Hot-Reload for Development

```sh
npm run dev
```

### Compile and Minify for Production

```sh
npm run build
```

### Lint with [ESLint](https://eslint.org/)

```sh
npm run lint
```
