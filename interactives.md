# Interactives

## Interactives types

### Dialog Cards
Este interactivo consiste en una serie de bloques, como si fueran cartas (con un desplazamiento horizontal que ocurre con un scroll horizontal cuando exceden el ancho del contenedor) las cuales llevan obligatoriamente para la parte frontal una imagen, pueden llevar también texto y audio (de manera opcional) y al hacer clic sobre el "card" muestra la otra cara (o la parte trasera) con información que puede ser [imagen, texto markdown, audio].

Este interactivo replica el comportamiento del [Dialog Card de H5P](https://h5p.org/dialog-cards#example=63129).

### Multiple choice
Es tal cual un cuestionario de opción múltiple con las características:
- Una o varias respuestas correctas
- Retroalimentación opcional por opción de respuesta (con bandera de correcto/incorrecto)
- Retroalimentación opcional global

### Fill the blanks
Es un texto que contiene espacios para integrar palabras por medio del llenado de campos de texto coin estas características:
- Las opciones de palabra correcta se separan por un "|" para que se pueda integrar más de una opción
- Se puede indicar si debe distinguir entre mayúsculas y minúsculas o no. Esto para el momento de la comparación poder integrar un procesamiento del texto adicional

### Drag the words
Parecido al fill the blanks pero en esta ocasión, en lugar de campos de texto son espacios droppables a los que el usuario puede arrastrar una palabra draggable. Entre sus características:
- Puede existir más de una opción de palabra correcta
- Cuando se repite la misma palabra más de una vez en el texto, cualquiera de las opciones draggables debe ser correcta en cualquiera de los campos que se responden con la misma palabra
- Las opciones de respuesta se separan en cada campo por un "|", al construir la vista se debe procesar estas opciones para generar los draggables

## Planning

### Contexto

`mod_bunamrag` ya soporta 4 tipos de bloque de contenido reales (`textcontent` con editor
markdown, `videourl` con embed de YouTube + transcripción accesible, `podcastaudio`, `h5p`).
El quinto tipo, `interactive`, hoy solo muestra un aviso "próximamente" en `view.php` — el
bloque existe en la BD (`blocktype = 'interactive'`) pero nunca se implementó su contenido
real. Este documento describe 4 variantes de interactivo que se quieren soportar (Dialog
Cards, Multiple choice, Fill the blanks, Drag the words), cada una con su propia estructura
de datos.

Decisiones ya confirmadas con el usuario para esta fase:
- **Autoría**: JSON crudo pegado/escrito en un textarea (igual que los demás bloques), con
  un selector de subtipo que precarga una plantilla, validación de esquema al guardar, y una
  vista previa (reutilizando el renderer real) debajo del bloque tras guardar. **No** se
  construye un formulario estructurado (agregar/quitar tarjetas, opciones, etc. desde UI).
- **Medios en Dialog Cards** (imagen obligatoria en frente, audio/imagen/texto opcionales en
  reverso): se referencian por **URL externa** dentro del JSON, igual que ya hace el bloque
  `videourl`. No se integra el File API de Moodle (draft areas, filepicker, `pluginfile.php`)
  en esta fase — `manage_content.php` no es un `moodleform` y agregar esa plomería es un
  esfuerzo separado y significativo (investigado: requeriría un `bunamrag_pluginfile()` en
  `lib.php` al estilo `mod/page/lib.php:319`, más un endpoint de subida dedicado). Se deja
  como trabajo futuro explícito si el usuario luego quiere subir archivos en vez de enlazar
  URLs.
- **Sintaxis de blanco** en Fill the blanks / Drag the words: `{{opción1|opción2}}` (no el
  asterisco propuesto originalmente arriba, que colisiona con la sintaxis de cursivas de
  Markdown). El campo de texto de estos dos tipos es **texto plano**, sin editor Markdown —
  son oraciones cortas de ejercicio, no contenido enriquecido.

### Modelo de datos

**Sin cambios de esquema en el JSON en sí.** El campo `content` (TEXT) de `bunamrag_content`
ya existe y puede guardar el JSON tal cual; `contentformat` no se usa para
`blocktype = 'interactive'` (se deja en su valor por default, igual que ya se ignora para
otros tipos no-texto).

Cada bloque interactivo es un único objeto JSON con un discriminador `type`:

```jsonc
{ "type": "dialogcards" | "multiplechoice" | "fillblanks" | "dragwords", ... }
```

#### `dialogcards`
```json
{
  "type": "dialogcards",
  "cards": [
    {
      "front": { "image": "https://...", "alt": "texto alternativo", "text": "opcional", "audio": "https://... (opcional)" },
      "back":  { "image": "https://... (opcional)", "text": "opcional (markdown corto)", "audio": "https://... (opcional)" }
    }
  ]
}
```
Validación: `cards` no vacío; cada `front.image` y `front.alt` obligatorios (accesibilidad:
toda imagen necesita alt); cada `back` debe tener al menos uno de `image`/`text`/`audio`.

#### `multiplechoice`
```json
{
  "type": "multiplechoice",
  "question": "markdown",
  "options": [
    { "text": "...", "correct": true, "feedback": "opcional" },
    { "text": "...", "correct": false, "feedback": "opcional" }
  ],
  "globalfeedback": [
    { "from": 90, "to": 100, "feedback": "..." },
    { "from": 80, "to": 90,  "feedback": "..." },
    { "from": 70, "to": 80,  "feedback": "..." },
    { "from": 0,  "to": 70,  "feedback": "..." }
  ]
}
```
Validación: `question` no vacío; ≥2 `options`; ≥1 con `correct: true`; el frontend decide
radio (una sola correcta) vs checkbox (varias correctas) contando cuántas `correct: true` hay.

`globalfeedback` es un arreglo ordenado de rangos de puntaje (0–100), estilo "overall
feedback" de H5P — opcional (puede omitirse u omitirse vacío si no se quiere feedback por
rango). Cada entrada requiere `from`/`to` numéricos y `feedback` no vacío. Los rangos pueden
compartir frontera (como en el ejemplo: 90 aparece en dos rangos) — la ambigüedad se resuelve
por **orden de declaración**: se evalúan en el orden del arreglo y se usa el primer rango
donde `from <= score <= to`, así que los rangos más específicos/altos deben ir primero.

Cálculo de `score` (0–100): porcentaje de opciones respondidas correctamente, contando tanto
las correctas marcadas como las incorrectas dejadas sin marcar, sobre el total de opciones —
esto da crédito parcial en preguntas de varias respuestas correctas y se reduce al 0/100
binario habitual cuando solo hay una opción correcta (radio).

#### `fillblanks` y `dragwords` (mismo esquema, distinto renderer)
```json
{
  "type": "fillblanks",
  "text": "El proceso se llama {{fotosíntesis|foto sintesis}} y ocurre en el {{cloroplasto}}.",
  "casesensitive": false,
  "globalfeedback": [
    { "from": 90, "to": 100, "feedback": "..." },
    { "from": 0,  "to": 90,  "feedback": "..." }
  ]
}
```
Validación: `text` no vacío y contiene ≥1 grupo `{{...}}`; alternativas de cada blanco se
obtienen partiendo por `|` y haciendo `trim()`; `globalfeedback` es el mismo formato de
rangos ordenados que en `multiplechoice` (opcional, misma regla de "primer rango que
coincide gana").

`score` (0–100) para estos dos tipos: porcentaje de blancos respondidos correctamente
(blancos correctos / total de blancos × 100).

Para `dragwords`, la interpretación acordada de "si la misma palabra se repite, cualquier
draggable con ese texto es válido en cualquier campo que la acepte" es: se genera **un chip
arrastrable por cada blanco** (usando su primera alternativa como texto visible del chip,
barajados), y al soltar un chip en un dropzone se evalúa correcto si `chip.texto` está entre
las alternativas de *ese* dropzone — no se ata el chip a "su" blanco de origen. Confirmar
este comportamiento en pruebas manuales durante la implementación.

### Backend

Decisión de arquitectura (a petición del usuario): **el HTML se compila una sola vez, al
guardar**, no en cada visita a `view.php`. Esto evita reprocesar el JSON (parseo + armado de
markup) en cada carga de página — el costo de compilar se paga una vez por edición, no una
vez por vista.

#### Cambio de esquema en `bunamrag_content`

Dos columnas nuevas (migración en `db/upgrade.php` + bump de versión, mismo patrón ya usado
para `transcript`):
- `interactivetype` — CHAR(20), nullable. El discriminador (`dialogcards` | `multiplechoice`
  | `fillblanks` | `dragwords`), duplicado desde el JSON como columna propia para no tener
  que decodificar el JSON solo para saber qué módulo AMD encolar en `view.php`.
- `renderedhtml` — TEXT, nullable. El HTML ya compilado, listo para `echo` directo.

Ambas solo se usan cuando `blocktype = 'interactive'`; quedan `null` para los demás tipos
(igual que `transcript` hoy solo aplica a `videourl`).

#### `classes/local/interactive_schema.php` (nueva clase) — fuente única de verdad

- `templates(): array` — JSON de plantilla por tipo (para precargar el textarea al elegir
  tipo nuevo).
- `validate(string $type, $decoded): array` — lista de strings de error (vacía si válido).
  Un método privado de validación por tipo; `fillblanks`/`dragwords` comparten validador.
- `render(string $type, $decoded, \context $context): string` — arma el HTML
  data-attribute-driven para el tipo (contenedor con `data-bunamrag-interactive="dialogcards"`
  etc. + el JSON de datos ya "traducido" embebido en un `<script type="application/json">`
  para que el módulo AMD lo lea en el cliente). Se llama **solo una vez, al guardar** (nunca
  desde `view.php`).

#### `manage_content.php`

- El case `interactive` dentro del `if/else if` de renderizado de celda (mismo patrón que ya
  existe para `textcontent`/`videourl`) agrega:
  - un `<select>` de subtipo — si el contenido actual está vacío, elegir un tipo dispara JS
    que precarga la plantilla de `interactive_schema::templates()`.
  - el `<textarea name="content">` con el **JSON crudo únicamente** — es lo único que el
    docente edita; nunca se le muestra el HTML compilado.
  - debajo, si `renderedhtml` ya existe (ediciones previas guardadas), la vista previa real:
    se hace `echo $block->renderedhtml` + se encola el AMD según `$block->interactivetype`,
    exactamente igual que hará `view.php`.
- El action `savecontent` (que hoy no distingue `blocktype`) primero obtiene el `blocktype`
  del bloque (`$DB->get_field('bunamrag_content', 'blocktype', [...], MUST_EXIST)`). Si es
  `interactive`:
  1. `json_decode($content)`; si falla o `interactive_schema::validate($decoded->type ?? '',
     $decoded)` devuelve errores → **no se guarda nada** (ni el JSON ni el HTML viejo se
     tocan) y se listan los errores con `\core\notification::error(...)` (persiste a través
     del `redirect()`, patrón estándar de Moodle).
  2. Si valida → `interactive_schema::render(...)` compila el HTML, y se actualizan las 3
     columnas juntas (`content`, `interactivetype`, `renderedhtml`) en un solo
     `update_record`.
  - Para los demás `blocktype` el comportamiento no cambia (solo se guarda `content`, y
    `transcript` cuando aplica).

#### `view.php`

El case `interactive` deja de mostrar siempre "próximamente": si `$block->renderedhtml` no
está vacío, hace `echo $block->renderedhtml` y encola el módulo AMD según
`$block->interactivetype`; si está vacío (bloque nunca guardado con JSON válido), muestra el
mismo aviso de siempre. No hay `json_decode` ni construcción de HTML en esta ruta — es una
lectura directa de columnas ya calculadas.

### Frontend (estudiante) — un módulo AMD por tipo

Todos siguen el patrón ya establecido en el plugin: operan sobre el HTML ya renderizado por
PHP vía `data-*` attributes + `$PAGE->requires->js_call_amd()`, sin dependencias externas
nuevas (se evita repetir la decisión de vendorizar una librería a menos que sea
imprescindible).

- **`amd/src/interactive_dialogcards.js`**: click/tecla Enter en una tarjeta alterna una
  clase `is-flipped` (flip con CSS `transform: rotateY()`); contenedor con
  `overflow-x: auto` para el scroll horizontal de tarjetas (sin JS adicional).
- **`amd/src/interactive_multiplechoice.js`**: botón "Comprobar" habilita cuando hay
  selección; compara marcado del usuario contra `correct` de cada opción; revela feedback
  por opción; calcula el `score` (0–100, ver esquema arriba) y recorre `globalfeedback` en
  orden buscando el primer rango que lo contenga, mostrándolo en una región
  `aria-live="polite"`.
- **`amd/src/interactive_fillblanks.js`**: por cada grupo `{{...}}` renderiza un
  `<input type="text">` inline; botón "Comprobar" compara valor contra alternativas
  (respetando `casesensitive`), marca cada input correcto/incorrecto.
- **`amd/src/interactive_dragwords.js`**: construido sobre **`core/dragdrop`** (ya usado en
  core, no es una librería nueva), siguiendo el patrón de
  `question/type/ddwtos/amd/src/ddwtos.js` (`dragDrop.prepare(e)` /
  `dragDrop.start(e, drag, onMove, onDrop)`). Accesibilidad: además del arrastre por
  puntero/touch, soporta operación por teclado (Tab al chip → Enter para "levantarlo" → Tab
  al dropzone → Enter para soltarlo), replicando el patrón accesible que ya usa `ddwtos` en
  el motor de quiz de Moodle — el drag-and-drop puramente visual no es accesible por sí solo.

Cada build se escribe a mano en `amd/build/*.min.js` (sin `grunt`/`node_modules` disponibles
en este entorno, mismo patrón ya usado para `markdowneditor.js`).

### Verificación

- Como docente: en `manage_content.php`, agregar un bloque `interactive`, elegir cada uno de
  los 4 subtipos y confirmar que la plantilla precarga JSON válido; guardar con JSON roto y
  confirmar que se bloquea con errores legibles vía `\core\notification::error`; guardar JSON
  válido y confirmar que aparece la vista previa real debajo del bloque.
- Como estudiante (`view.php`): para cada subtipo, confirmar que el interactivo se renderiza
  y es operable con mouse, touch, y **solo teclado** (tab a través de todos los controles);
  confirmar que un bloque `interactive` con JSON vacío/inválido muestra el aviso
  "próximamente" en vez de romper la página completa.
- Confirmar que ningún esquema de `interactive_schema::validate()` acepta contenido a medias
  (p. ej. `dialogcards` sin `alt`, `multiplechoice` sin ninguna opción correcta,
  `fillblanks`/`dragwords` sin ningún grupo `{{...}}`).
- Confirmar que editar y volver a guardar un bloque `interactive` actualiza `renderedhtml`
  (verificar directamente en BD o viendo que el cambio se refleje en `view.php`), y que un
  intento de guardar con JSON inválido deja `content`/`interactivetype`/`renderedhtml`
  intactos (no los pisa con nada roto).

### Fuera de alcance de esta fase (explícito)

- Subida de archivos (imagen/audio) vía File API de Moodle para Dialog Cards — se referencian
  por URL externa por ahora; requeriría un `bunamrag_pluginfile()` en `lib.php` + endpoint de
  subida dedicado + resolución vía `moodle_url::make_pluginfile_url()`.
- Validación de JSON en el cliente (JS) antes de enviar el formulario — por ahora la única
  validación es server-side al guardar, siguiendo el patrón ya usado en el resto del plugin.