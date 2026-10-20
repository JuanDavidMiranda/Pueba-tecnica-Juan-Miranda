-- =============================================================================
-- 1. SQL PRODUCTOS
-- Campos pedidos: id_fabricante, id_producto, descripcion, precio, existencias.
-- La columna se llama existencias (el enunciado la define así; el encabezado
-- de la tabla de ejemplo usa el singular "existencia").
--
-- id_producto no es único entre fabricantes: 41003 y Xk47 se repiten.
-- La clave primaria es el par (id_fabricante, id_producto).
-- =============================================================================

DROP TABLE IF EXISTS productos;

CREATE TABLE productos (
    id_fabricante VARCHAR(10) NOT NULL,
    id_producto VARCHAR(20) NOT NULL,
    descripcion VARCHAR(100) NOT NULL,
    precio DECIMAL(10, 2) NOT NULL,
    existencias INT NOT NULL,
    PRIMARY KEY (id_fabricante, id_producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO productos (id_fabricante, id_producto, descripcion, precio, existencias) VALUES
    ('Aci', '41001', 'Aguja', 58.00, 227),
    ('Aci', '41002', 'Micropore', 80.00, 150),
    ('Aci', '41003', 'Gasa', 112.00, 80),
    ('Aci', '41004', 'Equipo macrogoteo', 110.00, 50),
    ('Bic', '41003', 'Curas', 120.00, 20),
    ('Inc', '41089', 'Canaleta', 500.00, 30),
    ('Qsa', 'Xk47', 'Compresa', 150.00, 200),
    ('Bic', 'Xk47', 'Compresa', 200.00, 200);

-- -----------------------------------------------------------------------------
-- 1. Listado con precio e I.V.A. del 10 %.
--    precio_con_iva = precio * 1.10
--    Aguja 63.80, Micropore 88.00, Gasa 123.20, Equipo macrogoteo 121.00,
--    Curas 132.00, Canaleta 550.00, Compresa (Qsa) 165.00, Compresa (Bic) 220.00
-- -----------------------------------------------------------------------------
SELECT
    id_fabricante,
    id_producto,
    descripcion,
    precio,
    ROUND(precio * 1.10, 2) AS precio_con_iva
FROM productos
ORDER BY id_fabricante, id_producto;

-- -----------------------------------------------------------------------------
-- 2. Cantidad total de existencias por producto.
--    Se agrupa por descripción porque el mismo producto puede venir de más
--    de un fabricante (Compresa aparece dos veces y suma 400).
-- -----------------------------------------------------------------------------
SELECT
    descripcion AS producto,
    SUM(existencias) AS total_existencias
FROM productos
GROUP BY descripcion
ORDER BY descripcion;

-- -----------------------------------------------------------------------------
-- 3. Promedio de precio por fabricante.
--    Aci 90.00, Bic 160.00, Inc 500.00, Qsa 150.00
-- -----------------------------------------------------------------------------
SELECT
    id_fabricante,
    ROUND(AVG(precio), 2) AS promedio_precio
FROM productos
GROUP BY id_fabricante
ORDER BY id_fabricante;

-- -----------------------------------------------------------------------------
-- 4. Producto con mayor precio.
--    Se usa MAX para no perder empates. En estos datos es Canaleta (Inc, 500).
-- -----------------------------------------------------------------------------
SELECT
    id_fabricante,
    id_producto,
    descripcion,
    precio,
    existencias
FROM productos
WHERE precio = (SELECT MAX(precio) FROM productos);

-- -----------------------------------------------------------------------------
-- 5. Nuevo pedido de 500 Curas del fabricante Bic.
--    El producto ya existe (Bic, 41003, 20 unidades). El pedido entra al
--    inventario: 20 + 500 = 520. No se inserta otra fila porque la clave
--    primaria ya está ocupada por esas Curas.
-- -----------------------------------------------------------------------------
UPDATE productos
SET existencias = existencias + 500
WHERE id_fabricante = 'Bic'
  AND id_producto = '41003'
  AND descripcion = 'Curas';

SELECT id_fabricante, id_producto, descripcion, precio, existencias
FROM productos
WHERE id_fabricante = 'Bic' AND id_producto = '41003';

-- -----------------------------------------------------------------------------
-- 6. El fabricante Osa deja de ser proveedor.
--    La sentencia pedida es exactamente sobre 'Osa'.
--    En los datos cargados no existe Osa: el fabricante parecido es 'Qsa'.
--    Se ejecuta lo pedido (afecta 0 filas) y se deja comentada la sentencia
--    que cumpliría la intención de negocio si el nombre correcto es Qsa.
-- -----------------------------------------------------------------------------
DELETE FROM productos
WHERE id_fabricante = 'Osa';

-- DELETE FROM productos
-- WHERE id_fabricante = 'Qsa';

SELECT id_fabricante, id_producto, descripcion, existencias
FROM productos
ORDER BY id_fabricante, id_producto;
