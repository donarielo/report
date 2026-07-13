# Reporte de Ventas por Sucursal

Aplicación web simple para capturar y consultar reportes de ventas por sucursal, encargado y vendedor.

## Funcionalidades

- Alta de reportes con sucursal, encargado, vendedor, fecha, monto y comentarios.
- Persistencia local con `localStorage`.
- Filtros por sucursal, vendedor y rango de fechas.
- Resumen automático de registros, total de ventas, vendedores únicos y sucursales únicas.
- Exportación de los resultados filtrados a CSV.
- Opción para borrar todos los registros.

## Entorno de previsualización

### Opción 1: Preview local (Python)

```bash
npm run preview
```

- URL: `http://localhost:4173`

Si necesitas exponerlo en red local:

```bash
npm run preview:host
```

### Opción 2: Preview con Docker

```bash
docker compose up -d
```

- URL: `http://localhost:8080`

Para apagarlo:

```bash
docker compose down
```

## Uso

1. Abrir `index.html` en el navegador o levantar uno de los entornos de previsualización.
2. Capturar reportes desde el formulario "Nuevo reporte".
3. Usar los filtros para consultar información específica.
4. Exportar con el botón **Exportar CSV** cuando se necesite compartir el reporte.

## Nota

Los datos se guardan en el navegador del equipo (almacenamiento local), no en un servidor.
