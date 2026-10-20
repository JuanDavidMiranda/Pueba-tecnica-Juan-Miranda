# Respuestas de la prueba técnica

Completar antes de entregar: nombre y cédula del aspirante. No se inventan en este repositorio.

## 1. SQL de productos

El script ejecutable está en `database/sql/productos.sql`. La clave primaria es `(id_fabricante, id_producto)` porque el código `41003` y el código `Xk47` se repiten entre fabricantes. La columna de stock se llama `existencias`, que es el nombre definido en el enunciado.

### Listado con I.V.A. del 10 %

```sql
SELECT
    id_fabricante,
    id_producto,
    descripcion,
    precio,
    ROUND(precio * 1.10, 2) AS precio_con_iva
FROM productos
ORDER BY id_fabricante, id_producto;
```

| Fabricante | Producto | Descripción | Precio | Precio con IVA |
| --- | --- | --- | --- | --- |
| Aci | 41001 | Aguja | 58.00 | 63.80 |
| Aci | 41002 | Micropore | 80.00 | 88.00 |
| Aci | 41003 | Gasa | 112.00 | 123.20 |
| Aci | 41004 | Equipo macrogoteo | 110.00 | 121.00 |
| Bic | 41003 | Curas | 120.00 | 132.00 |
| Bic | Xk47 | Compresa | 200.00 | 220.00 |
| Inc | 41089 | Canaleta | 500.00 | 550.00 |
| Qsa | Xk47 | Compresa | 150.00 | 165.00 |

### Total de existencias por producto

Se agrupa por descripción. Compresa llega de dos fabricantes y su total es 400.

```sql
SELECT descripcion AS producto, SUM(existencias) AS total_existencias
FROM productos
GROUP BY descripcion
ORDER BY descripcion;
```

| Producto | Total |
| --- | --- |
| Aguja | 227 |
| Canaleta | 30 |
| Compresa | 400 |
| Curas | 20 |
| Equipo macrogoteo | 50 |
| Gasa | 80 |
| Micropore | 150 |

### Promedio de precio por fabricante

```sql
SELECT id_fabricante, ROUND(AVG(precio), 2) AS promedio_precio
FROM productos
GROUP BY id_fabricante
ORDER BY id_fabricante;
```

| Fabricante | Promedio |
| --- | --- |
| Aci | 90.00 |
| Bic | 160.00 |
| Inc | 500.00 |
| Qsa | 150.00 |

Aci = (58 + 80 + 112 + 110) / 4 = 90. Bic = (120 + 200) / 2 = 160.

### Producto con mayor precio

```sql
SELECT id_fabricante, id_producto, descripcion, precio, existencias
FROM productos
WHERE precio = (SELECT MAX(precio) FROM productos);
```

Inc, 41089, Canaleta, precio 500. La comparación contra `MAX` conserva un empate si hubiera otro producto al mismo precio.

### Pedido de 500 Curas de Bic

El producto ya existe: Bic, 41003, Curas, 20 unidades. El pedido aumenta el inventario; no se inserta otra fila porque la clave primaria ya está ocupada.

```sql
UPDATE productos
SET existencias = existencias + 500
WHERE id_fabricante = 'Bic'
  AND id_producto = '41003'
  AND descripcion = 'Curas';
```

Existencias resultantes: 520.

### Retiro del fabricante Osa

```sql
DELETE FROM productos
WHERE id_fabricante = 'Osa';
```

Esa sentencia afecta 0 filas. En los datos cargados no existe Osa; el fabricante parecido es Qsa. Si la intención es retirar al proveedor que sí está cargado, la sentencia sería:

```sql
DELETE FROM productos
WHERE id_fabricante = 'Qsa';
```

El script deja esa segunda sentencia comentada y conserva a Qsa, para ejecutar exactamente lo que pide el enunciado.

## 2. Conocimientos básicos de programación

### ¿Qué es una variable?

Una variable es un espacio de memoria con un nombre, usado para guardar un dato que puede cambiar durante la ejecución. Tiene identificador, tipo (explícito o inferido según el lenguaje) y valor. En PHP se escribe con `$`, por ejemplo `$edad = 30;`.

### ¿Qué es un ciclo? Mencione dos tipos

Un ciclo repite un bloque de instrucciones mientras una condición se cumple, o durante una cantidad conocida de vueltas.

- `for`: conviene cuando hay un contador o un número de repeticiones definido.
- `while`: conviene cuando la repetición depende de una condición que se evalúa antes de cada vuelta.

También existen `do-while` y `foreach`.

### ¿Qué es un condicional?

Es una estructura que elige qué instrucciones ejecutar según si una expresión es verdadera o falsa. En PHP se usa `if / else / elseif` y también `switch` o `match`.

### Nombre, edad, sexo, dirección y teléfono de un estudiante

Respuesta: **los atributos de una clase**.

Describen el estado del estudiante. No son operaciones (métodos) ni objetos: el objeto sería un estudiante concreto que posee esos atributos.

### ¿Qué es la programación orientada a objetos?

Es un paradigma que organiza el programa en objetos. Cada objeto reúne datos (atributos) y comportamiento (métodos). Se apoya en cuatro ideas:

- Abstracción: quedarse con lo relevante del problema.
- Encapsulamiento: ocultar el detalle interno y exponer una interfaz.
- Herencia: reutilizar y especializar tipos.
- Polimorfismo: tratar objetos distintos por medio de una misma interfaz.

### ¿Qué son los patrones de diseño y para qué sirven?

Son soluciones conocidas para problemas de diseño que se repiten. No son código para copiar: describen cómo colaboran las clases. Sirven para comunicar una decisión, bajar el acoplamiento y facilitar cambios.

En este proyecto se usan cuatro:

- Front Controller: `public/index.php` es la única entrada HTTP.
- MVC: la vista está en `views/` y `public/assets/`, los controladores en `src/Controller` y los datos en los repositorios.
- Repository: el SQL queda en clases PDO y no en los controladores.
- Middleware de autenticación: `Authenticate` valida el JWT y el enrutador lo aplica antes de llegar al censo.

### Operador de PHP que compara valor y tipo

Respuesta: **`===`**.

`==` compara con coerción de tipos. `===` exige el mismo valor y el mismo tipo. `||` es un OR lógico. `==?` no es un operador de PHP.

### Cero o string vacío en contexto booleano

Respuesta: **False**.

En PHP son falsos, entre otros, `false`, `0`, `0.0`, `""`, `"0"`, un array vacío y `null`. El cero y el string vacío se evalúan como falso. No lanzan un error. `null` es otro valor, también falso, pero la pregunta agrupa cero y string vacío, así que la opción correcta es False.

### Resultado del `for`

```php
$a = 1;
$b = 9;
for ($i = $a; $i <= $b; $i = $i + 1) {
    $c = $a + $i;
}
echo $c;
```

`$i` recorre 1, 2, 3, 4, 5, 6, 7, 8 y 9. En la última vuelta `$c = 1 + 9`. Cuando `$i` pasa a 10 la condición falla y el ciclo termina.

**Resultado: 10**

### Resultado del `while`

```php
$b = 1;
$c = 0;
while ($b < 11) {
    $c = $c + 1;
    $b = $b + 1;
}
echo $c;
```

El enunciado escribe `while $b < 11` sin paréntesis. En PHP esa línea no compila; la lógica prevista, ya con la sintaxis válida, es la de arriba. `$b` entra valiendo 1 y sale cuando llega a 11. El cuerpo corre con `$b` en 1, 2, 3, 4, 5, 6, 7, 8, 9 y 10: diez vueltas.

**Resultado: 10**

### Resultado de `$print`

```php
$a = 5;
$b = 6;
$x = 8;
$c = 20;
$print = "";
if ($a <= $b and $b >= $c) {
    if ($x <= $c and $c >= 10) {
        $print = "Feliz Navidad";
    }
} else {
    if ($c > $x or $x <= $b) {
        $print = "Feliz Año";
    }
}
echo $print;
```

La condición externa es `(5 <= 6) and (6 >= 20)`: verdadero y falso, así que es falsa. Se entra al `else`. Ahí `(20 > 8) or (8 <= 6)` es verdadero, así que se asigna el mensaje.

**Resultado: Feliz Año**
