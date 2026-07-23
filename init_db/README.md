# Base de datos - posventa2

Este archivo `posventa2.sql` contiene la estructura (CREATE TABLE) y los datos
actuales de la base de datos usada por este proyecto.

## Cómo restaurarla en otra máquina

1. Crear la base vacía:
   ```sql
   CREATE DATABASE posventa2 CHARACTER SET utf8mb4;
   ```
2. Importar el dump:
   ```bash
   mysql -u root -p posventa2 < init_db/posventa2.sql
   ```
   O desde DBeaver: clic derecho sobre la base `posventa2` → **Tools → Execute SQL Script** → seleccionar `posventa2.sql`.

## Configuración de conexión

Este proyecto se conecta vía `Backend/conexion.php`. Ajusta ahí `host`, `user`,
`password` y `database` según tu entorno local si son distintos a:

- host: `localhost`
- puerto: `3306`
- usuario: `root`
- password: *(vacío)*
- base: `posventa2`
