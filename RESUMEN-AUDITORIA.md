# Resumen de auditoría — Liga Potrero PHP

## Conclusión

La revisión estática detectó un riesgo importante de control de acceso: las rutas administrativas y las operaciones de escritura no parecen exigir autenticación. Se recomienda corregir este punto antes de publicar la aplicación.

## Hallazgos

| Prioridad | Hallazgo | Referencias |
|---|---|---|
| Alta | El panel y las API de escritura no aplican controles de acceso visibles. Esto incluye guardar, eliminar y subir imágenes. | [`public/index.php`](./public/index.php), [`includes/funciones.php`](./includes/funciones.php), [`controllers/ApiController.php`](./controllers/ApiController.php) |
| Media | El modelo construye consultas SQL concatenando valores y usando `addslashes`, en lugar de parámetros preparados. | [`models/ActiveRecord.php`](./models/ActiveRecord.php) |
| Media | Si faltan variables de entorno, la conexión intenta usar `root` con contraseña vacía. | [`includes/database.php`](./includes/database.php) |
| Media | Los errores de conexión de PDO se muestran directamente en la respuesta. | [`includes/database.php`](./includes/database.php) |
| Baja | Las rutas inexistentes muestran un mensaje, pero no establecen el estado HTTP 404. | [`Router.php`](./Router.php) |
| Precaución | El volcado SQL contiene datos de jugadores y sentencias `DROP TABLE`; debe mantenerse fuera de la raíz web y manejarse con cuidado. | [`mis_recursos/liga_potrero_php.sql`](./mis_recursos/liga_potrero_php.sql) |

## Recomendaciones prioritarias

1. Exigir autenticación y autorización en todas las rutas administrativas y de escritura, incluidas las de imágenes.
2. Reemplazar las consultas SQL concatenadas por consultas preparadas.
3. Exigir credenciales explícitas en producción y devolver mensajes genéricos ante errores de conexión.
4. Añadir pruebas de acceso anónimo y de permisos para operaciones de creación, edición, eliminación y subida de imágenes.
5. Evitar publicar el volcado SQL y revisar su contenido antes de compartirlo.

## Aspectos positivos observados

- La subida de imágenes limita tamaño y dimensiones, valida el tipo de archivo y genera nombres aleatorios.
- La API restringe los campos aceptados mediante listas permitidas.
- PDO está configurado para lanzar excepciones ante errores de base de datos.

## Alcance

Este resumen se basa en una revisión estática de rutas, controladores, modelos, configuración y el volcado SQL. No se modificaron ni ejecutaron pruebas contra la aplicación, y no se verificó la configuración del servidor o de la base de datos en funcionamiento.
