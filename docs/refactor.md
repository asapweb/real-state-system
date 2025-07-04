✅ Estrategia para refactorización completa con Cursor
🧭 Contexto
El proyecto sigue las reglas definidas en:

ARCHITECTURE.md (contrato API frontend-backend)

api/ARCHITECTURE.md (backend Laravel)

front/ARCHITECTURE.md (frontend Vue 3 + Vuetify)

Queremos aplicar refactors masivos en los controladores, enums y frontend.

🔁 Paso 1: Aplicar ApiResource en todos los controladores
🎯 Objetivo
Estandarizar el formato de respuesta de index() y show() en todas las entidades.

Asegurar salida como data: {...} o data: [...].

🛠️ Instrucciones para Cursor
📌 Ejecutar sobre todos los controladores en app/Http/Controllers/

En index():

Reemplazar cualquier retorno directo de Model::paginate(...) por:

php
Copy
Edit
return ModelResource::collection(
    Model::with([...])->paginate($perPage)
);
Si no existe el ModelResource, generarlo con php artisan make:resource.

En show():

Reemplazar cualquier return $model; por:

php
Copy
Edit
return new ModelResource($model->load([...]));
Generar el archivo ModelResource si no existe, incluyendo relaciones con whenLoaded.

🧮 Paso 2: Estandarizar los enums
🎯 Objetivo
Todas las clases de Enum deben usar PascalCase como nombre.

Todos los valores deben estar en SCREAMING_SNAKE_CASE.

🛠️ Instrucciones para Cursor
📌 Ejecutar sobre todos los archivos en app/Enums/

Verificar que todos los valores definidos en cada enum usen SCREAMING_SNAKE_CASE.

Ejemplo:

php
Copy
Edit
case FIXED = 'fixed';
case PERCENTAGE = 'percentage';
Renombrar el valor si no cumple y actualizar su uso en el código.

🔍 Paso 3: Corregir los usos de enums en el código
🎯 Objetivo
Si un enum fue corregido (valor cambiado), actualizar su uso en:

Validaciones (Rule::in([...]))

Tests

Casteo de valores

Comparaciones (if ($model->type === 'percentage'))

🛠️ Instrucciones para Cursor
📌 Buscar en app/, tests/ y database/seeders/

Para cada enum corregido en el paso 2:

Buscar todos los lugares donde se usa su valor ('percentage', 'fixed', etc.).

Reemplazar por el nuevo valor si cambió.

Asegurar que las comparaciones estén alineadas al enum definido.

🌐 Paso 4: Corregir el frontend
🎯 Objetivo
Todos los axios.get('/api/.../:id') deben desestructurar correctamente el data.

🛠️ Instrucciones para Cursor
📌 Buscar en front/views/ y front/services/

Para cada componente que hace axios.get(...):

Reemplazar:

js
Copy
Edit
const entity = response.data
por:

js
Copy
Edit
const entity = response.data.data
Verificar que ContractShow.vue, ClientShow.vue, CollectionShow.vue (y otros *.Show.vue) sigan este patrón.

🔒 Paso 5: Validación
Correr php artisan test para verificar que los tests de backend sigan pasando.

Testear carga en el frontend de cada entidad (index, show, edit) para validar que la API responde correctamente.

Verificar respuestas uniformes en Postman o desde devtools.

