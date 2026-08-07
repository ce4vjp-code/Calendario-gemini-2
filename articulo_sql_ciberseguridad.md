# Dominando SQL para la Ciberseguridad: De la Selección Básica a la Detección de Amenazas

En el panorama actual de la ciberseguridad corporativa, los datos son el activo más valioso y, al mismo tiempo, el mayor desafío. Plataformas de SIEM, *Data Lakes* y bases de datos relacionales almacenan diariamente petabytes de registros de red, intentos de acceso e inventarios de activos. 

Para un analista de seguridad en entornos como nuestra hipotética **SecureCorp**, **SQL (Structured Query Language)** no es solo un lenguaje de consulta: es la herramienta fundamental para interrogar la infraestructura, aislar anomalías y responder ante incidentes en tiempo real.

En este artículo, exploraremos la anatomía de las consultas SQL, el poder de sus operadores de filtrado y cómo aplicarlos en escenarios reales de ciberseguridad.

---

## 1. La Base de Todo: Proyección de Datos con `SELECT` y `FROM`

Toda investigación comienza delimitando qué datos necesitamos extraer. En lugar de consultar el comodín `SELECT *` —que puede saturar el rendimiento al traer millones de registros inútiles—, la mejor práctica en seguridad es seleccionar únicamente los campos relevantes para el análisis.

```sql
-- Extraer únicamente los identificadores de dispositivos y sus sistemas operativos
SELECT device_id, operating_system
FROM machines;
```

---

## 2. Filtrado Preciso: La Cláusula `WHERE` y sus Operadores

La cláusula `WHERE` actúa como nuestro primer cortafuegos analítico, reduciendo millones de entradas a los eventos exactos bajo investigación.

### A. Filtrado por Coincidencia Exacta y Rangos
Cuando investigamos eventos o logs en fechas o IDs específicos, utilizamos los operadores de comparación (`=`, `>=`, `<=`, `BETWEEN`).

```sql
-- Filtrar accesos a partir de una fecha específica
SELECT firstname, lastname, hiredate
FROM employees
WHERE hiredate >= '2003-10-17';

-- Identificar eventos de seguridad en un rango numérico de auditoría
SELECT event_id, username, login_date
FROM log_in_attempts
WHERE event_id BETWEEN 100 AND 150;
```

### B. Patrones de Texto con `LIKE` y Comodines (`%`)
En ciberseguridad, los datos raramente son homogéneos. El operador `LIKE` junto con el comodín `%` nos permite realizar búsquedas por patrones de texto (por ejemplo, nombres de servidores o convenciones de red).

```sql
-- Buscar todas las oficinas pertenecientes al edificio "South" (ej: south-109, south-201)
SELECT *
FROM employees
WHERE office LIKE 'south%';
```

### C. Evaluación Multicriterio con `IN`, `AND` y `OR`
Para auditar políticas regionales o combinar factores de riesgo:

```sql
-- Filtrar clientes en países específicos usando la cláusula IN
SELECT firstname, lastname, country
FROM customers
WHERE country IN ('Brazil', 'Argentina');

-- Filtrado estricto combinado (País y Estado específicos)
SELECT firstname, lastname, address, country
FROM customers
WHERE country = 'USA' AND state = 'CA';
```

---

## 3. Relacionando Fuentes de Datos: El Poder de los `JOINs`

Los datos de seguridad casi nunca viven en una sola tabla. Es necesario vincular el **inventario de usuarios** con el **historial de accesos** o la **lista de parches instalados**.

| Tipo de JOIN | Comportamiento en Seguridad |
| :--- | :--- |
| **`INNER JOIN`** | Devuelve solo los registros que coinciden en ambas tablas (ej: facturas que sí tienen detalle de ítems registrado). |
| **`LEFT JOIN`** | Devuelve **todas** las filas de la tabla izquierda y solo las coincidentes de la derecha. Excelente para encontrar ausencias. |
| **`RIGHT JOIN`** | Devuelve **todas** las filas de la tabla derecha y las coincidentes de la izquierda. |

### Ejemplo de `INNER JOIN`:
```sql
-- Relacionar facturas con el detalle de ítems mediante 'invoiceid'
SELECT customerid, trackid
FROM invoices
INNER JOIN invoice_items ON invoices.invoiceid = invoice_items.invoiceid;
```

---

## 4. Casos de Uso Reales en Ciberseguridad

Veamos cómo estos conceptos se aplican en la práctica dentro del Centro de Operaciones de Seguridad (SOC):

### Caso 1: Detección de Ataques de Fuerza Bruta
Combinando `WHERE`, `GROUP BY` y `HAVING`, podemos identificar direcciones IP que registran una cantidad anómala de autenticaciones fallidas.

```sql
SELECT ip_address, username, COUNT(*) AS intentos_fallidos
FROM log_in_attempts
WHERE status = 'failed'
GROUP BY ip_address, username
HAVING COUNT(*) > 50;
```

### Caso 2: Auditoría de Equipos sin Parches Críticos (Exclusión con `LEFT JOIN`)
Para encontrar los activos expuestos a una vulnerabilidad crítica (ej: `KB-2026-001`), utilizamos un `LEFT JOIN` filtrando donde el registro de la tabla derecha sea nulo (`IS NULL`):

```sql
SELECT m.device_id, m.hostname, m.operating_system
FROM machines m
LEFT JOIN installed_patches p 
  ON m.device_id = p.device_id AND p.patch_id = 'KB-2026-001'
WHERE p.patch_id IS NULL;
```

### Caso 3: Investigación Forense tras una Brecha de Datos
Durante la investigación de un incidente de exfiltración, rastreamos ejecuciones fuera del horario laboral con volúmenes anómalos de descarga:

```sql
SELECT timestamp, username, ip_address, table_name, rows_returned, query_text
FROM database_audit_logs
WHERE rows_returned > 5000
  AND TIME(timestamp) NOT BETWEEN '08:00:00' AND '20:00:00'
ORDER BY timestamp DESC;
```

---

## Conclusión

El dominio de SQL transforma a un analista pasivo en un **Threat Hunter activo**. Comprender la sintaxis de filtrado, las relaciones entre tablas y la agregación de datos permite no solo reaccionar eficazmente ante incidentes, sino también construir métricas automáticas de cumplimiento normativo y mantener blindada la infraestructura de la organización.
