# Modelo de Datos

Este directorio documenta el **diseño del modelo de datos** del proyecto, incluyendo el modelo Entidad/Relación, el modelo relacional y las decisiones técnicas asociadas.

Este README actúa como **documento vivo**, versionado con Git, donde se registran razonamientos, cambios y previsiones de escalabilidad. Los PDFs asociados representan **versiones consolidadas** para entrega.

---

## 1. Alcance de esta fase

En esta fase se ha definido:

- Modelo Entidad/Relación (E/R)
- Modelo Relacional derivado
- Identificación de entidades principales
- Relaciones, cardinalidades y restricciones
- Estados de pedidos y reservas

No se abordan todavía:

- Lógica de negocio
- Validaciones de aplicación
- Implementación física (SQL)

---

## 2. Decisiones de diseño clave

### 2.1 Estados de pedido y reserva

- Los **estados** no se modelan como ENUMs.
- Se definen como **tablas independientes**.

**Motivo**:

- Evitar rigidez
- Permitir nuevos estados sin migraciones críticas
- Facilitar auditoría y trazabilidad

Ejemplos:

- Pedido: pendiente, confirmado, preparado, entregado, cancelado
- Reserva: solicitada, confirmada, completada, no_presentado, cancelada

---

### 2.2 Separación Pedido / Reserva

- Pedido y reserva son entidades distintas.

**Motivo**:

- Ciclos de vida diferentes
- Reglas de negocio distintas
- Evitar tablas infladas con campos condicionales

---

### 2.3 Normalización

- Modelo normalizado hasta **3FN**.
- No se introducen redundancias prematuras.

La desnormalización se valorará únicamente en fase de rendimiento.

---

### 2.4 Campos temporales

- En fases posteriores podría añadirse trazabilidad temporal (fecha_creacion, fecha_actualizacion)
  en entidades clave como PEDIDO, RESERVA o COMERCIO.

**Motivo**:

- Auditoría
- Debug
- Escalabilidad futura

---

## 3. Escalabilidad prevista

Decisiones tomadas pensando en crecimiento futuro:

- Multi-comercio
- Posible multi-sucursal por comercio
- Pagos parciales o anticipos
- Promociones y eventos
- Histórico de estados
- Notificaciones (PWA)

Estas funcionalidades **no se implementan ahora**, pero el modelo no las bloquea.

---

## 4. Archivos del directorio

- `Entidad_Relacion.drawio` → Diagrama E/R editable
- `Entidad_Relacion.pdf` → Versión consolidada del E/R
- `Modelo_Relacional.pdf` → Modelo relacional
- `README.md` → Documento vivo de decisiones

---

## 5. Criterio de actualización

- Cambios conceptuales → actualizar README
- Cambios estructurales relevantes → regenerar PDF
- Ideas futuras → solo README

---

## 6. Estado actual

✔ Modelo E/R completo
✔ Modelo relacional coherente
✔ Estados definidos conceptualmente

Pendiente:

- Script SQL
- Seeds iniciales
- Diseño de API / lógica de negocio
