# Sistema De Ventas, Inventario Y Caja

[![Packagist](https://img.shields.io/packagist/v/facebook/php-business-sdk.svg?style=flat-square)](https://packagist.org/packages/facebook/php-business-sdk)

## Introduccion

Software para Puntos de Venta, Ferreterías, Bodegas, Farmacias y muchos otros negocios podrán gestionarse fácilmente, controle fácilmente su stock de Productos agrupe sus productos por Categorías, Marcas, controle las Unidades de Medida de cada uno de ellos , Registre sus Ventas las cuales disminuirán automáticamente el stock de sus productos al igual que las Compras adicionarán el stock de ellos, sepa cuánto gana que artículos vende más, cuanto artículos tiene en su Stock cual es el precio de sus artículos, sepa cuanto compró este mes , cuanto gastó, obtenga el inventario valorizado de su negocio con un solo click.

## Instalación

Primero restauramos la base de datos MySQL, después se tiene que agregar los valores correspondiente en el archivo `sistema/configuracion.php`:

```php
    define('HOST',		'127.0.0.1');
    define('USER',		'root');
    define('PASSWORD',	'');
    define('PORT',		'database port');
    define('DB',		'database name');

```
## Modulos

```
Módulo Multi-usuario

Módulo Multi-Caja

Módulo Multi-establecimiento

Módulo de Productos

Módulo de Ventas

Módulo de Clientes

Módulo de Proveedores

Módulo de Categorías (Departamentos)

Módulo de Kardex (General/Productos)

Módulo de Notificaciones

Módulo de Inventario

Módulo de Entradas y Salidas de Dinero

Módulo de Caja Chica

Módulo de Cortes de Caja

Módulo de Multi-Tabs Caja Temporal(Multiples clientes, Bares, Restaurantes, entre otro
```
# proyecto-ventas
