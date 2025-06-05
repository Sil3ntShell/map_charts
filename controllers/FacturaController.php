<?php

namespace Controllers;

use Exception;
use Model\ActiveRecord;
use Model\Ventas;
use Model\VentaDetalles;
use MVC\Router;

require_once __DIR__ . '/../vendor/autoload.php';

class FacturaController extends ActiveRecord
{

    public static function generarPDF()
    {
        try {
            $id_venta = filter_var($_GET['venta_id'], FILTER_SANITIZE_NUMBER_INT);
            
            if (!$id_venta) {
                throw new Exception('ID de venta no válido');
            }

            $venta = VentaController::ObtenerVentaPorId($id_venta);
            if (!$venta) {
                throw new Exception('Venta no encontrada');
            }

            $detalles_venta = VentaController::ObtenerDetallesPorVenta($id_venta);

            $pdf_mpdf = new \Mpdf\Mpdf([
                'format' => 'Letter',
                'orientation' => 'P',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 16,
                'margin_bottom' => 16,
                'margin_header' => 9,
                'margin_footer' => 9
            ]);

            $pdf_mpdf->SetTitle('Factura #' . $id_venta);
            $pdf_mpdf->SetAuthor('Sistema de Ventas');
            $pdf_mpdf->SetCreator('Sistema de Facturación');
            $pdf_mpdf->SetSubject('Factura de Venta');

            $contenido_html = self::GenerarHTMLFactura($venta, $detalles_venta);

            $pdf_mpdf->WriteHTML($contenido_html);

            $nombre_archivo = 'Factura_' . str_pad($id_venta, 6, '0', STR_PAD_LEFT) . '_' . date('Y-m-d_H-i-s') . '.pdf';

            $pdf_mpdf->Output($nombre_archivo, 'I'); // 'I' = mostrar en navegador, 'D' = descargar

        } catch (Exception $excepcion) {
            error_log("Error generando factura PDF: " . $excepcion->getMessage());
            
            echo "<div style='padding: 20px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px; font-family: Arial, sans-serif;'>";
            echo "<h3>Error al generar la factura</h3>";
            echo "<p>No se pudo generar la factura solicitada. Por favor, intente nuevamente.</p>";
            echo "<p><strong>Detalle del error:</strong> " . $excepcion->getMessage() . "</p>";
            echo "</div>";
        }
    }

    private static function GenerarHTMLFactura($datos_venta, $detalles_productos)
    {
        $fecha_formateada = date('d/m/Y H:i', strtotime($datos_venta['venta_fecha']));
        $numero_factura = str_pad($datos_venta['venta_id'], 6, '0', STR_PAD_LEFT);
        
        $contenido_html = '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Factura #' . $numero_factura . '</title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    font-size: 12px;
                    margin: 0;
                    padding: 0;
                    line-height: 1.4;
                }
                .encabezado {
                    background-color: #007bff;
                    color: white;
                    padding: 20px;
                    text-align: center;
                    margin-bottom: 20px;
                }
                .nombre-empresa {
                    font-size: 24px;
                    font-weight: bold;
                    margin-bottom: 5px;
                }
                .info-empresa {
                    font-size: 14px;
                }
                .info-factura {
                    margin-bottom: 20px;
                    border: 1px solid #ddd;
                    padding: 15px;
                    border-radius: 5px;
                }
                .info-factura h3 {
                    margin-top: 0;
                    color: #007bff;
                    border-bottom: 2px solid #007bff;
                    padding-bottom: 5px;
                }
                .datos-cliente {
                    background-color: #f8f9fa;
                    padding: 15px;
                    margin-bottom: 20px;
                    border-left: 4px solid #007bff;
                    border-radius: 0 5px 5px 0;
                }
                .tabla-productos {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                    border-radius: 5px;
                    overflow: hidden;
                }
                .tabla-productos th {
                    background-color: #007bff;
                    color: white;
                    padding: 12px;
                    text-align: left;
                    border: 1px solid #ddd;
                    font-weight: bold;
                }
                .tabla-productos td {
                    padding: 10px;
                    border: 1px solid #ddd;
                }
                .tabla-productos tr:nth-child(even) {
                    background-color: #f8f9fa;
                }
                .fila-total {
                    background-color: #e3f2fd !important;
                    font-weight: bold;
                    font-size: 14px;
                }
                .texto-derecha {
                    text-align: right;
                }
                .texto-centro {
                    text-align: center;
                }
                .pie-pagina {
                    margin-top: 30px;
                    text-align: center;
                    font-size: 10px;
                    color: #666;
                    border-top: 1px solid #ddd;
                    padding-top: 15px;
                }
                .numero-factura {
                    font-size: 18px;
                    font-weight: bold;
                    color: #007bff;
                }
                .info-adicional {
                    margin-top: 30px; 
                    padding: 15px; 
                    background-color: #f8f9fa; 
                    border-left: 4px solid #28a745;
                    border-radius: 0 5px 5px 0;
                }
                .contenedor-flex {
                    display: flex; 
                    justify-content: space-between;
                    flex-wrap: wrap;
                }
                @media print {
                    body { font-size: 11px; }
                    .encabezado { background-color: #007bff !important; }
                }
            </style>
        </head>
        <body>
            <div class="encabezado">
                <div class="nombre-empresa">SISTEMA DE VENTAS</div>
                <div class="info-empresa">
                    Dirección: Ciudad de Guatemala, Guatemala<br>
                    Teléfono: (502) 2345-6789 | Email: ventas@sistema.com<br>
                    NIT: 123456789
                </div>
            </div>

            <div class="info-factura">
                <h3>INFORMACIÓN DE FACTURA</h3>
                <div class="contenedor-flex">
                    <div>
                        <strong>Número de Factura:</strong> <span class="numero-factura">#' . $numero_factura . '</span><br>
                        <strong>Fecha de Emisión:</strong> ' . $fecha_formateada . '<br>
                        <strong>Estado:</strong> <span style="color: #28a745; font-weight: bold;">PAGADA</span>
                    </div>
                    <div style="text-align: right;">
                        <strong>Serie:</strong> A001<br>
                        <strong>Folio:</strong> ' . $numero_factura . '<br>
                        <strong>Moneda:</strong> Quetzales (GTQ)
                    </div>
                </div>
            </div>

            <div class="datos-cliente">
                <h3 style="margin-top: 0; color: #007bff;">DATOS DEL CLIENTE</h3>
                <strong>Nombre Completo:</strong> ' . htmlspecialchars($datos_venta['usuario_nombres'] . ' ' . $datos_venta['usuario_apellidos']) . '<br>
                <strong>Correo Electrónico:</strong> ' . htmlspecialchars($datos_venta['usuario_correo']) . '<br>
                <strong>ID Cliente:</strong> #' . str_pad($datos_venta['usuario_id'] ?? '000', 4, '0', STR_PAD_LEFT) . '
            </div>

            <h3 style="color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 5px;">DETALLE DE PRODUCTOS</h3>
            <table class="tabla-productos">
                <thead>
                    <tr>
                        <th style="width: 8%;">#</th>
                        <th style="width: 42%;">Producto</th>
                        <th style="width: 12%;" class="texto-centro">Cantidad</th>
                        <th style="width: 18%;" class="texto-derecha">Precio Unitario</th>
                        <th style="width: 20%;" class="texto-derecha">Subtotal</th>
                    </tr>
                </thead>
                <tbody>';

        $contador_productos = 1;
        $subtotal_general = 0;
        
        foreach ($detalles_productos as $detalle_producto) {
            $subtotal_producto = $detalle_producto['detalle_subtotal'];
            $subtotal_general += $subtotal_producto;
            
            $contenido_html .= '
                    <tr>
                        <td class="texto-centro">' . $contador_productos . '</td>
                        <td>' . htmlspecialchars($detalle_producto['pro_nombre']) . '</td>
                        <td class="texto-centro">' . number_format($detalle_producto['detalle_cantidad'], 0) . '</td>
                        <td class="texto-derecha">Q. ' . number_format($detalle_producto['detalle_precio_unitario'], 2) . '</td>
                        <td class="texto-derecha">Q. ' . number_format($subtotal_producto, 2) . '</td>
                    </tr>';
            $contador_productos++;
        }

        $porcentaje_iva = 0.12;
        $monto_iva = $subtotal_general * $porcentaje_iva;
        $total_con_iva = $subtotal_general + $monto_iva;

        $contenido_html .= '
                    <tr style="background-color: #f1f3f4;">
                        <td colspan="4" class="texto-derecha"><strong>Subtotal:</strong></td>
                        <td class="texto-derecha"><strong>Q. ' . number_format($subtotal_general, 2) . '</strong></td>
                    </tr>
                    <tr style="background-color: #f1f3f4;">
                        <td colspan="4" class="texto-derecha"><strong>IVA (12%):</strong></td>
                        <td class="texto-derecha"><strong>Q. ' . number_format($monto_iva, 2) . '</strong></td>
                    </tr>
                    <tr class="fila-total">
                        <td colspan="4" class="texto-derecha"><strong>TOTAL A PAGAR:</strong></td>
                        <td class="texto-derecha"><strong>Q. ' . number_format($datos_venta['venta_total'], 2) . '</strong></td>
                    </tr>
                </tbody>
            </table>

            <div class="info-adicional">
                <h4 style="margin-top: 0; color: #28a745;">INFORMACIÓN ADICIONAL</h4>
                <p style="margin: 5px 0;"><strong>Método de Pago:</strong> Efectivo / Tarjeta</p>
                <p style="margin: 5px 0;"><strong>Condiciones:</strong> Venta realizada al contado</p>
                <p style="margin: 5px 0;"><strong>Garantía:</strong> Según políticas del establecimiento</p>
                <p style="margin: 5px 0;"><strong>Válida por:</strong> 30 días a partir de la fecha de emisión</p>
            </div>

            <div class="pie-pagina">
                <p><strong>¡Gracias por su compra!</strong></p>
                <p>Esta factura fue generada electrónicamente el ' . date('d/m/Y H:i') . '</p>
                <p>Sistema de Ventas - Todos los derechos reservados © ' . date('Y') . '</p>
                <p style="font-size: 9px; margin-top: 10px;">
                    Para cualquier consulta o reclamo, contacte a nuestro servicio al cliente<br>
                    Tel: (502) 2345-6789 | Email: soporte@sistema.com
                </p>
            </div>
        </body>
        </html>';

        return $contenido_html;
    }

    public static function previsualizarFactura()
    {
        try {
            $id_venta = filter_var($_GET['venta_id'], FILTER_SANITIZE_NUMBER_INT);
            
            if (!$id_venta) {
                throw new Exception('ID de venta no válido');
            }

            $datos_venta = VentaController::ObtenerVentaPorId($id_venta);
            if (!$datos_venta) {
                throw new Exception('Venta no encontrada');
            }
            
            $detalles_venta = VentaController::ObtenerDetallesPorVenta($id_venta);

            $contenido_html = self::GenerarHTMLFactura($datos_venta, $detalles_venta);
            
            $contenido_html = str_replace('<body>', '<body style="background-color: #f5f5f5; padding: 20px;">', $contenido_html);
            
            echo $contenido_html;

        } catch (Exception $excepcion) {
            echo "<div style='padding: 20px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px; font-family: Arial, sans-serif;'>";
            echo "<h3>Error al generar la previsualización</h3>";
            echo "<p>No se pudo generar la previsualización de la factura.</p>";
            echo "<p><strong>Detalle del error:</strong> " . $excepcion->getMessage() . "</p>";
            echo "</div>";
        }
    }

    public static function descargarFactura()
    {
        try {
            $id_venta = filter_var($_GET['venta_id'], FILTER_SANITIZE_NUMBER_INT);
            
            if (!$id_venta) {
                throw new Exception('ID de venta no válido');
            }

            $venta = VentaController::ObtenerVentaPorId($id_venta);
            if (!$venta) {
                throw new Exception('Venta no encontrada');
            }

            $detalles_venta = VentaController::ObtenerDetallesPorVenta($id_venta);

            $pdf_mpdf = new \Mpdf\Mpdf([
                'format' => 'Letter',
                'orientation' => 'P',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 16,
                'margin_bottom' => 16,
            ]);

            $pdf_mpdf->SetTitle('Factura #' . $id_venta);
            $pdf_mpdf->SetAuthor('Sistema de Ventas');

            $contenido_html = self::GenerarHTMLFactura($venta, $detalles_venta);
            $pdf_mpdf->WriteHTML($contenido_html);

            $nombre_archivo = 'Factura_' . str_pad($id_venta, 6, '0', STR_PAD_LEFT) . '_' . date('Y-m-d_H-i-s') . '.pdf';

            $pdf_mpdf->Output($nombre_archivo, 'D');

        } catch (Exception $excepcion) {
            error_log("Error descargando factura PDF: " . $excepcion->getMessage());
            echo "Error al descargar la factura: " . $excepcion->getMessage();
        }
    }
}