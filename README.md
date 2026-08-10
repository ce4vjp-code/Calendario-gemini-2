# 📅 Calendario de Evaluaciones y Horarios Escolares

Plataforma web completa para la gestión y visualización del calendario de pruebas, evaluaciones y **horarios de clases** de un establecimiento educacional. Diseñada bajo un patrón de **"Lectura Pública, Escritura Privada"**: los alumnos y apoderados pueden ver el calendario y los horarios sin necesidad de registrarse, mientras que la creación y edición está restringida estrictamente a los profesores y administradores mediante un sistema de inicio de sesión seguro.

---

## 🚀 Características Principales

*   **Calendario Dinámico:** Visualización mensual en formato cuadrícula y formato de lista.
*   **Módulo de Horarios:** Gestión y visualización interactiva de la malla semanal de clases para cada curso.
*   **Filtros Globales:** Búsqueda rápida por Curso, Asignatura y Profesor (exclusivo para el Administrador).
*   **Gestión de Permisos:** 
    *   *Visitantes:* Solo lectura.
    *   *Profesores:* Pueden crear y editar únicamente sus propias evaluaciones.
    *   *Administrador:* Control total sobre todas las evaluaciones, además de generar códigos de invitación y gestionar cuentas.
*   **Exportación a PDF:** Generación nativa de comprobantes del calendario en formato PDF para impresión o envío.
*   **Sistema de Invitaciones:** Los profesores solo pueden registrarse mediante un código de un solo uso generado por el Administrador.

---

## 🛡️ Mejoras Recientes de Seguridad (Auditoría OWASP)

Se realizó una refactorización profunda del backend (PHP) para llevar la plataforma a un nivel de seguridad empresarial:

1.  **Protección contra Fuerza Bruta:** Implementación de la tabla `login_attempts`. Bloquea la dirección IP del atacante por 15 minutos tras 5 intentos fallidos, previniendo así la Denegación de Servicio (DoS) a usuarios legítimos.
2.  **Mitigación de Inyección SQL (SQLi):** Uso estricto de *PDO Prepared Statements* en todas las consultas. Se eliminaron todas las concatenaciones de variables de usuario.
3.  **Sanitización contra XSS:** Todas las entradas de texto (RUT, Nombre, Observaciones) son desinfectadas mediante `htmlspecialchars(..., ENT_QUOTES)` antes de almacenarse en la base de datos.
4.  **Prevención de Race Conditions:** La validación de los códigos de invitación para el registro se convirtió en una operación de actualización atómica en MySQL, imposibilitando el uso doble de un código incluso al mismo milisegundo.
5.  **Defensa contra Suplantación (IDOR):** Las evaluaciones se vincularon de forma nativa a la llave foránea `usuario_id` (ID interno numérico e inmutable) de cada profesor, en lugar de utilizar su nombre (falsificable).
6.  **Protección de Errores de Servidor:** Se silenciaron las excepciones de PHP hacia el cliente, mostrando errores genéricos para no filtrar detalles estructurales del servidor al atacante.
7.  **Seguridad de Sesiones:** Uso estricto de `session_regenerate_id(true)` para evitar fijación de sesión, bloqueo por concurrencia (1 dispositivo a la vez por profesor) y timeout automático de 30 minutos.

---

## 🐛 Registro de Errores Históricos y sus Soluciones

Durante el desarrollo y pruebas en entornos de hosting compartidos (cPanel/LiteSpeed), se detectaron y resolvieron los siguientes casos de borde:

### 1. Falso Positivo de Transacción de Base de Datos
*   **Problema:** Al ejecutar el parche de base de datos (`parche_db_seguridad.php`), PHP arrojaba el error *"There is no active transaction"* a pesar de haber creado las columnas con éxito.
*   **Causa:** Comandos DDL (como `CREATE TABLE` o `ALTER TABLE`) provocan *commits implícitos* y automáticos en MySQL, destruyendo la transacción PHP en curso.
*   **Solución:** Se eliminó la estructura de bloque de transacción de PHP (`beginTransaction` y `commit`) para scripts que modifican la estructura de la tabla, dejando las peticiones lineales.

### 2. Bloqueo de Visualización Pública
*   **Problema:** Los alumnos y apoderados (sin iniciar sesión) veían el calendario vacío tras un parche de seguridad.
*   **Causa:** El archivo `get_evaluaciones.php` había sido incluido accidentalmente en el bloque de exigencia de sesión de las demás APIs.
*   **Solución:** Se eliminó la condición `if (!isset($_SESSION['user_id']))` exclusivamente de este archivo, restableciendo el diseño arquitectónico de "Lectura Pública".

### 3. Caché Agresiva en Servidores (Evaluaciones "Fantasma")
*   **Problema:** Aunque la plataforma guardaba nuevas evaluaciones, los visitantes anónimos no las veían, pero los profesores logeados sí.
*   **Causa:** Servidores con sistemas de aceleración como *LiteSpeed Cache* asumen que pueden almacenar una copia congelada de una petición `GET` de un visitante anónimo, pero entregan datos reales cuando detectan la cookie `PHPSESSID`.
*   **Solución:** Se implementó una táctica de evasión de caché. Primero, se inyectaron cabeceras estrictas (`X-LiteSpeed-Cache-Control: no-cache`). Segundo, y como regla definitiva, se cambió el método `fetch()` de JavaScript de `GET` a `POST`, forzando al motor proxy a derivar la petición directamente a PHP ignorando cualquier nivel de caché.

### 4. Modo Offline y Alucinación de Guardado
*   **Problema:** Al agregar una evaluación, esta aparecía en el calendario pero desaparecía misteriosamente al recargar la página.
*   **Causa:** Un error de sintaxis en `add_evaluacion.php` provocaba un error HTTP 500 en el servidor. El script JavaScript interpretaba la caída del servidor como una falta de conexión a internet, activando un antiguo "Modo Offline" (`localStorage_fallback`) para uso de prototipos que dibujaba la evaluación en el DOM sin haber tocado la base de datos.
*   **Solución:** Se arregló la sintaxis en el archivo PHP para restaurar la conexión a BD y **se extirpó por completo todo el bloque de código del modo "Fallback" en JavaScript**. Ahora, el frontend respeta obligatoria y estrictamente el código de estado devuelto por el servidor, informando al usuario en color rojo si la base de datos falla.

---

## ⚙️ Instrucciones de Despliegue (Producción)

1. Sube todos los archivos (HTML, CSS, JS y la carpeta `/api`) al directorio público de tu cPanel (usualmente `public_html`).
2. Configura las credenciales de tu base de datos MySQL en el archivo `config.php`.
3. Ejecuta los archivos `parche_db.php`, `parche_db_observaciones.php`, `parche_db_seguridad.php` y `parche_db_horarios.php` **solo una vez** ingresando a sus URLs directamente en el navegador web para ensamblar la estructura de la base de datos.
4. (Opcional) Ejecuta `importar_datos_horarios.php` para cargar automáticamente todos los cursos y asignaturas por defecto en el sistema de horarios.
5. **Crítico:** Borra todos los archivos que comiencen con `parche_db...` y `importar_datos...` del servidor para prevenir alteraciones futuras.
6. Indícale a tus usuarios que fuercen el borrado de caché de sus navegadores locales (Ctrl + F5) para asegurar la carga del último `script.js`.

> 🔒 **Nota de Seguridad:** Las credenciales de base de datos se leen ahora de forma segura desde variables de entorno (`.env`).
