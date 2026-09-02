# PKTechnologies - Figma Interactiva

## Archivos
- `PKTechnologies_Figma_Import.html` → 4 frames divididos (Login, OTP 6 celdas, Inicio, Email) + Design System. Importar con plugin **HTML to Figma** (Builder.io).
- `PKTechnologies_Plugin_Interactivo.js` → Código para Figma Plugin que genera las 4 pantallas automáticamente con el tema azul oscuro.

## Cómo hacerla interactiva (30s)
### Opción A: HTML to Figma (recomendada)
1. Figma Desktop → New File
2. Plugins → HTML to Figma → Import → selecciona `PKTechnologies_Figma_Import.html`
3. Te crea 4 frames. Luego ve a tab **Prototype**:
   - Selecciona el botón **Sign In** en Frame 01 → `+` → **Navigate to** → Frame 02 (OTP) → `On Tap` + `Smart animate 300ms`
   - Selecciona **Confirmar código** en Frame 02 → Navigate to → Frame 03 (Inicio)
   - Selecciona **Cerrar sesión** en Frame 03 → Navigate to → Frame 01 (Login)
   - Selecciona cualquier elemento en Email → no necesita interacción

### Opción B: Plugin
1. Figma → Plugins → Development → New Plugin → `PKTechnologies`
2. Pega el contenido de `PKTechnologies_Plugin_Interactivo.js` en `code.js` → Run
3. Te genera las 4 pantallas; luego añade las interacciones como en Opción A (la API de plugins no permite crear reactions automáticamente en todos los planes).

## Exportar .fig
Una vez importado, haz `File → Save as .fig` o `File → Export → .fig` para tener el archivo Figma nativo dividido en pantallas e interactivo.

## Tokens
- --bg-deep #070b1a
- --card-bg #141f35
- --input-bg #0e1a30
- --accent #3b82f6
