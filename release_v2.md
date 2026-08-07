# Release v2.0 - Módulo de Horarios y Mejoras de Navegación 🚀

Estamos muy emocionados de presentar la versión 2.0 del sistema, la cual transforma la plataforma de un simple "Calendario de Evaluaciones" a una suite completa de **Gestión Escolar**.

## 🆕 Nuevas Características

### 1. Módulo Completo de Horarios de Clases
Se ha integrado un sistema totalmente nuevo y paralelo para gestionar los horarios de los cursos:
*   **Vista Pública (`horarios.html`):** Los alumnos y apoderados pueden consultar la malla semanal de clases (Lunes a Viernes, con sus respectivos bloques y recreos) filtrando fácilmente por su curso. No requiere inicio de sesión.
*   **Impresión Optimizada:** Se incorporó un botón dedicado para imprimir los horarios. Gracias a estilos CSS adaptativos (`@media print`), al imprimir se oculta toda la interfaz de navegación dejando únicamente la tabla de clases limpia y lista para entregar en papel o exportar como PDF.
*   **Panel de Administración (`admin_horarios.html`):** Interfaz gráfica drag-and-drop / interactiva para el armado de mallas.
    *   Gestión CRUD completa (Crear, Leer, Actualizar, Borrar) para **Cursos** y **Asignaturas**.
    *   Asignación dinámica de clases a bloques específicos (1° a 8° bloque) haciendo clic en las celdas vacías.
    *   Soporte para colores distintivos por asignatura para un reconocimiento visual rápido.
    *   Opción para limpiar bloques (botón rojo de eliminación inteligente).

### 2. Navegación Unificada e Institucional
*   Se agregó un botón de acceso directo ("Home") al sitio principal de la institución (`https://new.liceotpggm.cl`) en la cabecera principal del calendario.
*   El logo de la institución en la vista de horarios ahora redirige también al sitio oficial.
*   Botones de navegación bidireccionales fluidos entre el Calendario de Evaluaciones y el Horario de Clases.

## 🛠 Cambios Técnicos y Estructurales

*   **Nuevas Tablas de Base de Datos:** Se introduce `parche_db_horarios.php` para generar las tablas relacionales (`horario_cursos`, `horario_asignaturas` y `horario_clases`) con llaves foráneas y eliminación en cascada (ON DELETE CASCADE) para garantizar la integridad referencial.
*   **API RESTful:** Creación del directorio `/api/horarios/` con endpoints modulares en PHP (`admin_add_curso.php`, `public_get_horario.php`, etc.).
*   **Seguridad Heredada:** Todas las APIs administrativas de horarios reutilizan la lógica de sesiones segura (`check_session.php`) implementada en la versión anterior, previniendo accesos no autorizados.
*   **Script de Carga Masiva:** Se incluye `importar_datos_horarios.php` para llenar automáticamente la base de datos con el catálogo maestro de 18 cursos y 38 asignaturas institucionales, ahorrando horas de trabajo manual en el primer despliegue.

## 📝 Instrucciones de Actualización para Administradores

1. Sube todos los archivos nuevos a tu servidor, sobreescribiendo los antiguos (`index.html`, `style.css`).
2. Ingresa a `tusitio.com/parche_db_horarios.php` para instalar las nuevas tablas.
3. Ingresa a `tusitio.com/importar_datos_horarios.php` para pre-cargar los cursos y asignaturas base.
4. **Borra** ambos archivos PHP (`parche_...` e `importar_...`) de tu servidor por seguridad.
5. ¡Listo! Ya puedes empezar a armar los horarios en el Panel de Control.
