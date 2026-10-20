Respuestas de la prueba técnica

Nombre: Juan David Miranda Cuellar
Cc: 1.107.975.777

1. SQL de productos

El script ejecutable está en database/sql/productos.sql.

Listado con IVA 

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


Total de existencias por producto

Se agrupa por descripción. Compresa llega de dos fabricantes y su total es 400.

```sql
SELECT descripcion AS producto, SUM(existencias) AS total_existencias
FROM productos
GROUP BY descripcion
ORDER BY descripcion;
```


Aguja : 227 
Canaleta : 30 
Compresa : 400 
Curas : 20 
Equipo macrogoteo : 50 
Gasa : 80 
Micropore: 150 

Promedio de precio por fabricante

```sql
SELECT id_fabricante, ROUND(AVG(precio), 2) AS promedio_precio
FROM productos
GROUP BY id_fabricante
ORDER BY id_fabricante;
```

Aci : 90.00 
Bic : 160.00 
Inc : 500.00 
Qsa : 150.00 

Aci = (58 + 80 + 112 + 110) / 4 = 90. Bic = (120 + 200) / 2 = 160.

Producto con mayor precio

```sql
SELECT id_fabricante, id_producto, descripcion, precio, existencias
FROM productos
WHERE precio = (SELECT MAX(precio) FROM productos);
```


Pedido de 500 Curas de Bic



```sql
UPDATE productos
SET existencias = existencias + 500
WHERE id_fabricante = 'Bic'
  AND id_producto = '41003'
  AND descripcion = 'Curas';
```

Resultado existencias: 520.

Retiro del fabricante Osa

```sql
DELETE FROM productos
WHERE id_fabricante = 'Osa';
```



```sql
DELETE FROM productos
WHERE id_fabricante = 'Qsa';
```



2. Conocimientos básicos de programación

¿Qué es una variable?

Una variable es un espacio de memoria , usado para guardar iformación.
Por ejemplo podemos crear una variable llamada edad y guardar en ella el valor 20. Esto se puede utilizar para hacer cálculos o tomar decisiones.

¿Qué es un ciclo? Mencione dos tipos

Un ciclo repite un bloque de instrucciones mientras una condición se cumple, o durante una cantidad conocida de vueltas.

for: Se usa cuando hay un contador o un número de repeticiones definido.
while: Se usa cuando la repetición depende de una condición que se evalúa antes de cada vuelta.


¿Qué es un condicional?

Es una estructura que elige qué instrucciones ejecutar según si una expresión es verdadera o falsa. 

¿Nombre, edad, sexo, dirección y teléfono de un estudiante?

Esto hace referencia a los atributos de una clase.

¿Qué es la programación orientada a objetos?

Es el paradigma que organiza el desarrollo en objetos. Cada objeto tiene sus atributos y metodos. Y tiene cuatro conceptos principales: Abstracción, Encapsulamiento, Herencia, Polimorfismo.


¿Qué son los patrones de diseño y para qué sirven?

los patrones de diseño sirven para escribir código más organizado, flexible y fácil de mantener. Por ejemplo en este proyecto se utilizo el patron de diseño MVC.


¿Operador de PHP que compara valor y tipo?

En PHP se utiliza === para comparar tanto valor como tipo.

¿Cero o string vacío en contexto booleano?

En PHP el 0 el string vacion son igual a false.

Resultado del for?

```php
$a = 1;
$b = 9;
for ($i = $a; $i <= $b; $i = $i + 1) {
    $c = $a + $i;
}
echo $c;
```

El resultado de el for es 10 porque el rango es hasta cuando b es igual a 9 y cuando hace esta iteración c es igual a 10 y se termina el ciclo.

Resultado del while

```php
$b = 1;
$c = 0;
while ($b < 11) {
    $c = $c + 1;
    $b = $b + 1;
}
echo $c;
```

El resultado es 10 porque b esta inicializado en 1 y ek ciclo while va hasta que este valga 11 por lo tango se hacen 10 iteraciones.

Resultado de $print

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

El resultado es Feliz año porque en la condiciones del if se utiliza and que indica que las dos condicones deben ser verdaderas pero una es verdadera ya otra es falsa por lo tanto se va al bloque else donde tambien hay una condicione verdader y otra falsa pero al estar utilizandose el or si se ejecuta el if y imprime el feliz año.
