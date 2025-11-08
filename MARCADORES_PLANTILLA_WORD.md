# Marcadores Disponibles para Plantilla de Paquetería

## 📋 DATOS DEL REMITENTE

| Marcador | Descripción | Ejemplo |
|----------|-------------|---------|
| `${Nombre_Remitente}` | Nombre de la empresa | DISTRIBUIDORA ELÉCTRICA ASCENCIO SA DE CV |
| `${Nombre_Sucursal}` | Nombre de la sucursal | DEASA, FESA, TAPATIA, etc. |
| `${Direccion_Remitente}` | Dirección completa de la sucursal | Avenida Alemania 1255 en la Colonia Moderna... |
| `${Telefono_Remitente}` | Teléfono de contacto | 36141989 |

## 📍 DATOS DEL DESTINATARIO

| Marcador | Descripción | Ejemplo |
|----------|-------------|---------|
| `${Nombre_Destinatario}` | Nombre completo del destinatario | Alex Casillas |
| `${Direccion_Completa}` | Dirección completa formateada | Alemania 123 #45 Int. 2, entre Morelos y Hidalgo, Col. Moderna, C.P. 44190, Guadalajara, Jalisco |
| `${Telefono_Destinatario}` | Teléfono principal | 3312345678 |
| `${Contacto_Destinatario}` | Contacto adicional | Juan Pérez |

### Campos Individuales de Dirección (opcionales)

| Marcador | Descripción |
|----------|-------------|
| `${Calle}` | Nombre de la calle |
| `${No_Exterior}` | Número exterior |
| `${No_Interior}` | Número interior |
| `${Entre_Calles}` | Entre qué calles |
| `${Colonia}` | Colonia |
| `${Codigo_Postal}` | Código postal |
| `${Ciudad}` | Ciudad |
| `${Estado_Destino}` | Estado |

## 📦 DATOS DE PAQUETERÍA

| Marcador | Descripción | Ejemplo |
|----------|-------------|---------|
| `${Nombre_Paqueteria}` | Nombre de la paquetería | D8A, Estafeta, FedEx |
| `${Tipo_Cobro}` | Tipo de cobro | OCURRE X COBRAR, PREPAGADO |
| `${ATN}` | Atención a | Departamento de Compras |
| `${Num_Cliente}` | Número de cliente | 123456 |
| `${Clave_SAT}` | Clave SAT | ABC123 |

## 📅 OTROS DATOS

| Marcador | Descripción | Ejemplo |
|----------|-------------|---------|
| `${Referencia}` | Folio de factura o ID del pedido | FAC-12345 o PED-3780 |
| `${FechaHoy}` | Fecha actual en español | 7 de noviembre del 2025 |

## 🔄 Marcadores de Compatibilidad (aún funcionan)

Estos marcadores aún funcionan para compatibilidad con plantillas antiguas:

| Marcador Antiguo | Equivalente Nuevo |
|------------------|-------------------|
| `${Cliente}` | `${Nombre_Destinatario}` |
| `${Direccion}` | `${Direccion_Completa}` |
| `${Telefono}` | `${Telefono_Destinatario}` |

---

## 📝 Cómo Actualizar tu Plantilla Word

1. **Abre** el archivo: `Machotes/Paqueteria/Plantilla_Paqueteria.docx`

2. **Borra** todo el contenido actual

3. **Copia** el contenido del archivo ejemplo: `PLANTILLA_ACTUALIZADA_GUIA.txt`

4. **Pega** en Word y ajusta el formato:
   - Fuente: Arial o similar
   - Tamaño: 10-12pt
   - Ajusta márgenes según necesites

5. **Organiza** en secciones:
   ```
   ════════════════════════════════
   REMITENTE
   ════════════════════════════════
   Nombre: ${Nombre_Remitente}
   Sucursal: ${Nombre_Sucursal}
   Dirección: ${Direccion_Remitente}
   Teléfono: ${Telefono_Remitente}

   ════════════════════════════════
   DESTINATARIO
   ════════════════════════════════
   Nombre: ${Nombre_Destinatario}
   Dirección: ${Direccion_Completa}
   Teléfono: ${Telefono_Destinatario}

   ════════════════════════════════
   DATOS DE PAQUETERÍA
   ════════════════════════════════
   Paquetería: ${Nombre_Paqueteria}
   Tipo de Cobro: ${Tipo_Cobro}
   ATN: ${ATN}
   Contacto: ${Contacto_Destinatario}
   Núm. Cliente: ${Num_Cliente}
   Clave SAT: ${Clave_SAT}
   ```

6. **Guarda** el archivo

## ✅ Ejemplo de Resultado

Cuando descargues una plantilla con datos capturados, verás algo así:

```
════════════════════════════════
REMITENTE
════════════════════════════════
Nombre: DISTRIBUIDORA ELÉCTRICA ASCENCIO SA DE CV
Sucursal: DEASA
Dirección: Avenida Alemania 1255 en la Colonia Moderna con Código Postal 44190 en Guadalajara, Jalisco.
Teléfono: 36141989

════════════════════════════════
DESTINATARIO
════════════════════════════════
Nombre: Alex Casillas
Dirección: Alemania 123 #45, Col. Moderna, C.P. 44190, Guadalajara, Jalisco
Teléfono: 666666

════════════════════════════════
DATOS DE PAQUETERÍA
════════════════════════════════
Paquetería: D8A
Tipo de Cobro: OCURRE X COBRAR
ATN: Departamento de Compras
Contacto: Prueba
Núm. Cliente: 12345
Clave SAT: ABC123
```

## 🆘 Soporte

Si un marcador no se reemplaza (aparece como `${Nombre_Campo}`):
1. Verifica que el marcador esté escrito **exactamente** como se muestra (mayúsculas, minúsculas y guiones bajos)
2. Asegúrate de que los símbolos `${ }` estén completos
3. Verifica que hayas capturado los datos en el modal antes de descargar la plantilla
