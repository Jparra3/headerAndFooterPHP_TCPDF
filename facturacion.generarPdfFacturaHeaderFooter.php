<?php

session_start();
set_time_limit(0);
date_default_timezone_set("America/Bogota");

require_once '../../Plantillas/lib/tcpdf/tcpdf.php';
require_once '../entidad/Transaccion.php';
require_once '../modelo/Transaccion.php';
require_once '../entidad/TransaccionProducto.php';
require_once '../modelo/TransaccionProducto.php';
require_once '../entidad/TransaccionCruce.php';
require_once '../modelo/TransaccionCruce.php';
require_once '../comunes/funciones.php';
require_once '../entidad/TransaccionImpuesto.php';
require_once '../modelo/TransaccionImpuesto.php';

$retorno = array('exito' => 1, 'mensaje' => '', 'ruta' => '');

class mipdf extends TCPDF {

    private $htmlHeader;
    private $htmlFooter;
    private $rutaFondoPdf;
    private $tamanioFuente;

    function getTamanioFuente() {
        return $this->tamanioFuente;
    }

    function setTamanioFuente($tamanioFuente) {
        $this->tamanioFuente = $tamanioFuente;
    }
    
    function getRutaFondoPdf() {
        return $this->rutaFondoPdf;
    }

    function getHtmlHeader() {
        return $this->htmlHeader;
    }

    function getHtmlFooter() {
        return $this->htmlFooter;
    }

    function setRutaFondoPdf($rutaFondoPdf) {
        $this->rutaFondoPdf = $rutaFondoPdf;
    }

    function setHtmlHeader($htmlHeader) {
        $this->htmlHeader = $htmlHeader;
    }

    function setHtmlFooter($htmlFooter) {
        $this->htmlFooter = $htmlFooter;
    }

    //Header personalizado
    public function Header() {
        $this->SetFont('helvetica', '', $this->tamanioFuente);

        if ($this->rutaFondoPdf != "" && $this->rutaFondoPdf != null && $this->rutaFondoPdf != "null") {
            // get the current page break margin
            $bMargin = $this->getBreakMargin();
            // get current auto-page-break mode
            $auto_page_break = $this->AutoPageBreak;
            // disable auto-page-break
            $this->SetAutoPageBreak(false, 0);
            // set bacground image
            $this->Image($this->rutaFondoPdf, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
            // restore auto-page-break status
            $this->SetAutoPageBreak($auto_page_break, $bMargin);
            // set the starting point for the page content
            $this->setPageMark();
        }

        $this->writeHTML($this->htmlHeader, true, false, true, false, '');
    }

    //footer personalizado
    public function Footer() {
        // posicion
        $this->SetY(-100);
        // fuente
        $this->SetFont('helvetica', '', $this->tamanioFuente);
        // numero de pagina
        $this->writeHTML($this->htmlFooter, true, false, true, false, '');
    }

}

try {

    $objTransaccion = new \modelo\Transaccion(new \entidad\Transaccion());
    $arrEstilosFactura = $objTransaccion->obtenerEstilosFactura();
    if ($arrEstilosFactura["exito"] == 0) {
        throw new Exception($arrEstilosFactura["mensaje"]);
    }

    $arrMes = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
    $dia = date("d");
    $mes = (int) date("m") - 1;
    $anio = date("Y");
    $hora = date("H");
    $minuto = date("i");
    $segundo = date("s");
    $ampm = strtoupper(date("a"));
    
    $numeroRegistrosPorPagina = $arrEstilosFactura["data"]["numeroRegistrosPagina"];
    $rutaLogo = $arrEstilosFactura["data"]["rutaLogoEmpresa"];
    $fondoPdf = $arrEstilosFactura["data"]["rutaFondo"];
    $fondoTitulos = $arrEstilosFactura["data"]["colorFondoTitulos"];
    $fondoContenido = $arrEstilosFactura["data"]["colorFondoContenido"];
    $colorTexto = $arrEstilosFactura["data"]["colorTextoContenido"];
    $colorTextoTitulo = $arrEstilosFactura["data"]["colorTextoTitulos"];
    $tamanioFuente = $arrEstilosFactura["data"]["tamanioFuente"];
    
    /*$idTransaccion = 1838;
    $valorTotalPagar = 43124;
    $valorTotalPagado = 43124;
    $localStorage = "/Faiho/";
    $_SESSION["idUsuario"] = 305;
     */
    
    //variables POST
    $idTransaccion = $_POST["idTransaccion"];
    $valorTotalPagar = $_POST["valorTotalPagar"];
    $valorTotalPagado = $_POST["valorTotalPagado"];
    $localStorage = $_POST["localStorage"];
    

    $transaccionE = new \entidad\Transaccion();
    $transaccionE->setIdTransaccion($idTransaccion);
    $transaccionM = new \modelo\Transaccion($transaccionE);
    $arrInfoEmpresa = $transaccionM->obtenerInfoEmpresaUsuario();
    $arrInfoFactura = $transaccionM->obtenerInfoFactura();
    $arrInfoCajero = $transaccionM->obtenerInfoCajero();
    $arrInfoCliente = $transaccionM->obtenerInfoCliente();
    $arrInfoConcepto = $transaccionM->obtenerInfoConcepto();
    $arrMuestraEmpaque = $transaccionM->obtenerMuestraEmpaque();

    $transaccionProductoE = new \entidad\TransaccionProducto();
    $transaccionProductoE->setTransaccion($transaccionE);
    $transaccionProductoM = new \modelo\TransaccionProducto($transaccionProductoE);
    $arrInfoProductos = $transaccionProductoM->consultarProductosFactura();
    
    $transaccionImpuestoE = new \entidad\TransaccionImpuesto();
    $transaccionImpuestoE->setIdTransaccion($idTransaccion);
    $transaccionImpuestoM = new \modelo\TransaccionImpuesto($transaccionImpuestoE);
    $arrInfoImpuestos = $transaccionImpuestoM->consultar();
    
    $transaccionCruceE = new \entidad\TransaccionCruce();
    $transaccionCruceE->setIdTransaccionConceptoAfectado($arrInfoConcepto["idTransaccionConcepto"]);
    $transaccionCruceM = new \modelo\TransaccionCruce($transaccionCruceE);
    $arrInfoFormasPago = $transaccionCruceM->consultarFormasPagoFactura();

    //ruta
    $retorno["ruta"] = "../../archivos" . $localStorage . "facturas/";

    //Creamos la ruta si no existe
    if (!file_exists($retorno["ruta"])) {
        mkdir($retorno["ruta"], 0777, true);
    }
    $retorno["ruta"] .= "factura_" . $arrInfoFactura["numeroFactura"] . ".pdf";

    //Encabezado
    //$htmlHead = '<br><br><br><br><br><br>';
    $htmlHead .= '<table style="width:100%;">';
    $htmlHead .= '<tr><td>&nbsp;</td></tr>';
    $htmlHead .= '<tr>';
    $htmlHead .= '<td style="width:33%">';
    $htmlHead .= '<img src="' . $rutaLogo . '">';
    $htmlHead .= '</td>';
    $htmlHead .= '<td style="width:34%;text-align:center;">';
    $htmlHead .= $arrInfoEmpresa["empresa"];
    $htmlHead .= '<br>';
    $htmlHead .= 'NIT ' . $arrInfoEmpresa["nit"] . ' - ' . $arrInfoEmpresa["digitoVerificacion"];
    $htmlHead .= '<br>';
    $htmlHead .= $arrInfoEmpresa["direccion"];
    $htmlHead .= '<br>';
    $htmlHead .= 'Tel. ' . $arrInfoEmpresa["telefono"];
    $htmlHead .= '<br>';
    $htmlHead .= $arrInfoEmpresa["email"];
    $htmlHead .= '</td>';
    $htmlHead .= '<td style="width:33%;">';
    $htmlHead .= '<table style="width:100%;">';
    $htmlHead .= '<tr>';

    if ($arrInfoCajero["prefijo"] != null && $arrInfoCajero["prefijo"] != '' && $arrInfoCajero["prefijo"] != 'null') {
        $htmlHead .= '<td style="font-size:14px;"><b>Factura de venta ' . $arrInfoCajero["prefijo"] . ' - ' . str_pad($arrInfoFactura["numeroFactura"], 40, " ", STR_PAD_BOTH) . '</b></td>';
    } else {
        $htmlHead .= '<td><b>Factura de venta ' . str_pad($arrInfoFactura["numeroFactura"], 40, " ", STR_PAD_BOTH) . '</b></td>';
    }

    $htmlHead .= '</tr>';
    $htmlHead .= '<tr>';
    $htmlHead .= '<td><b>Fecha:</b> ' . $arrMes[$mes] . ' ' . $dia . ' de ' . $anio . ' ' . $hora . ':' . $minuto . ':' . $segundo . ' ' . $ampm . '</td>';
    $htmlHead .= '</tr>';
    $htmlHead .= '<tr>';
    $htmlHead .= '<td><b>Fecha Vcto:</b> ' . $arrInfoFactura["fechaVencimiento"] . '</td>';
    $htmlHead .= '</tr>';
    $htmlHead .= '<tr>';
    $htmlHead .= '<td><b>Cajero:</b> ' . $arrInfoCajero["usuario"] . '</td>';
    $htmlHead .= '</tr>';
    $htmlHead .= '<tr><td>&nbsp;</td></tr>';
    $htmlHead .= '</table>';
    $htmlHead .= '</td>';
    $htmlHead .= '</tr>';
    $htmlHead .= '</table>';

    //Info cliente
    $htmlHead .= '<br><br>';
    $htmlHead .= '<table style="width:100%;">';
    $htmlHead .= '<tr>';
    $htmlHead .= '<td style="width: 15%; background-color:' . $fondoTitulos . ';color:' . $colorTextoTitulo . ';border:0.7px #8000FF solid;"> CLIENTE </td>';
    $htmlHead .= '<td style="width: 50%; border:0.7px #E6E7E8 solid;color:' . $colorTexto . ';"> ' . $arrInfoCliente["tercero"] . ' </td>';
    $htmlHead .= '<td style="width: 15%; background-color:' . $fondoTitulos . ';color:' . $colorTextoTitulo . ';border:0.7px #E6E7E8 solid;"> NIT </td>';
    $htmlHead .= '<td style="width: 20%; border:0.7px #E6E7E8 solid;color:' . $colorTexto . ';"> ' . $arrInfoCliente["nit"] . ' </td>';
    $htmlHead .= '</tr>';
    $htmlHead .= '<tr>';
    $htmlHead .= '<td style="width: 15%; background-color:' . $fondoTitulos . ';color:' . $colorTextoTitulo . ';border:0.7px #8000FF solid;"> DIRECCIÓN </td>';
    $htmlHead .= '<td style="width: 27%; border:0.7px #E6E7E8 solid;color:' . $colorTexto . ';"> ' . $arrInfoCliente["direccion"] . ' </td>';
    $htmlHead .= '<td style="width: 8%; background-color:' . $fondoTitulos . ';color:' . $colorTextoTitulo . ';border:0.7px #8000FF solid;"> CIUDAD </td>';
    $htmlHead .= '<td style="width: 15%; border:0.7px #E6E7E8 solid;color:' . $colorTexto . ';"> ' . $arrInfoCliente["municipio"] . ' </td>';
    $htmlHead .= '<td style="width: 15%; background-color:' . $fondoTitulos . ';color:' . $colorTextoTitulo . ';border:0.7px #E6E7E8 solid;"> TELÉFONO </td>';
    $htmlHead .= '<td style="width: 20%; border:0.7px #E6E7E8 solid;color:' . $colorTexto . ';"> ' . $arrInfoCliente["telefono"] . ' </td>';
    $htmlHead .= '</tr>';
    $htmlHead .= '</table>';

    //Productos
    $htmlBodyHead .= '<tr>';
    $htmlBodyHead .= '<td style="width: 7%; background-color:' . $fondoTitulos . ';color:' . $colorTextoTitulo . '; text-align:center;border:0.7px #E6E7E8 solid;">COD</td>';
    $htmlBodyHead .= '<td style="width: 25%; background-color:' . $fondoTitulos . ';color:' . $colorTextoTitulo . '; text-align:center;border:0.7px #E6E7E8 solid;">PRODUCTO</td>';
    $htmlBodyHead .= '<td style="width: 11%; background-color:' . $fondoTitulos . ';color:' . $colorTextoTitulo . '; text-align:center;border:0.7px #E6E7E8 solid;">U.MED</td>';
    $tamanioTdNota = 21;
    if($arrMuestraEmpaque['muestraEmpaque'] == 1){
        $tamanioTdNota = 10;
        $htmlBodyHead .= '<td style="width: 11%; background-color:' . $fondoTitulos . ';color:' . $colorTextoTitulo . '; text-align:center;border:0.7px #E6E7E8 solid;">EMPAQUE</td>';
    }
    $htmlBodyHead .= '<td style="width: '.$tamanioTdNota.'%; background-color:' . $fondoTitulos . ';color:' . $colorTextoTitulo . '; text-align:center;border:0.7px #E6E7E8 solid;">NOTA</td>';
    $htmlBodyHead .= '<td style="width: 7%; background-color:' . $fondoTitulos . ';color:' . $colorTextoTitulo . '; text-align:center;border:0.7px #E6E7E8 solid;">%IVA</td>';
    $htmlBodyHead .= '<td style="width: 7%; background-color:' . $fondoTitulos . ';color:' . $colorTextoTitulo . '; text-align:center;border:0.7px #E6E7E8 solid;">CANT</td>';
    $htmlBodyHead .= '<td style="width: 11%; background-color:' . $fondoTitulos . ';color:' . $colorTextoTitulo . '; text-align:center;border:0.7px #E6E7E8 solid;">V.UNIT.</td>';
    $htmlBodyHead .= '<td style="width: 11%; background-color:' . $fondoTitulos . ';color:' . $colorTextoTitulo . '; text-align:center;border:0.7px #E6E7E8 solid;">TOTAL</td>';
    $htmlBodyHead .= '</tr>';

    $contador = 0;
    $numPags = 0;
    $arrHtmlBody = array();
    while ($contador < count($arrInfoProductos)) {

        if (($contador % $numeroRegistrosPorPagina == 0) || $contador == 0) {
            if ($contador != 0) {
                $htmlBody .= '</table>';
                $arrHtmlBody[$numPags] = $htmlBody;
                $htmlBody = '';
            }

            $numPags++;
            $htmlBody .= '<table style="width: 100%" >';
            $htmlBody .= $htmlBodyHead;
        }

        $htmlBody .= '<tr>';
        $htmlBody .= '<td style="background-color:' . $fondoContenido . ';border:0.7px #E6E7E8 solid;color:' . $colorTexto . ';text-align:center;">' . $arrInfoProductos[$contador]["codigo"] . '</td>';
        $htmlBody .= '<td style="background-color:' . $fondoContenido . ';border:0.7px #E6E7E8 solid;color:' . $colorTexto . ';">' . $arrInfoProductos[$contador]["producto"] . '</td>';
        $htmlBody .= '<td style="background-color:' . $fondoContenido . ';border:0.7px #E6E7E8 solid;color:' . $colorTexto . ';">' . $arrInfoProductos[$contador]["unidadMedida"] . '</td>';
        if($arrMuestraEmpaque['muestraEmpaque'] == 1)
            $htmlBody .= '<td style="background-color:' . $fondoContenido . ';border:0.7px #E6E7E8 solid;color:' . $colorTexto . ';">' . $arrInfoProductos[$contador]["presentacion"] . '</td>';
        if ($arrInfoProductos[$contador]["nota"] != null && $arrInfoProductos[$contador]["nota"] != 'null' && $arrInfoProductos[$contador]["nota"] != '') {
            $htmlBody .= '<td style="background-color:' . $fondoContenido . ';border:0.7px #E6E7E8 solid;color:' . $colorTexto . ';">' . $arrInfoProductos[$contador]["nota"] . '</td>';
        } else {
            $htmlBody .= '<td style="background-color:' . $fondoContenido . ';border:0.7px #E6E7E8 solid;color:' . $colorTexto . ';"> </td>';
        }
        $htmlBody .= '<td style="background-color:' . $fondoContenido . ';border:0.7px #E6E7E8 solid;color:' . $colorTexto . ';text-align:center;">' . $arrInfoProductos[$contador]['iva'] . '</td>';
        $htmlBody .= '<td style="background-color:' . $fondoContenido . ';border:0.7px #E6E7E8 solid;color:' . $colorTexto . ';text-align:right;">' . number_format($arrInfoProductos[$contador]["cantidad"], 2, ',', '.') . '</td>';
        $htmlBody .= '<td style="background-color:' . $fondoContenido . ';border:0.7px #E6E7E8 solid;color:' . $colorTexto . ';text-align:right;">' . number_format($arrInfoProductos[$contador]["valorUnitaSalidConImpue"], 2, ',', '.') . '</td>';
        $htmlBody .= '<td style="background-color:' . $fondoContenido . ';border:0.7px #E6E7E8 solid;color:' . $colorTexto . ';text-align:right;">' . number_format($arrInfoProductos[$contador]["valorUnitaSalidConImpue"] * $arrInfoProductos[$contador]["cantidad"], 0, '', '.') . '</td>';
        $htmlBody .= '</tr>';
        $contador++;
    }

    if ((count($arrInfoProductos) % $numeroRegistrosPorPagina != 0) || ((count($arrInfoProductos) == $numeroRegistrosPorPagina)) || ($contador % $numeroRegistrosPorPagina == 0)) {
        $htmlBody .= '</table>';
        $arrHtmlBody[$numPags] = $htmlBody;
        $htmlBody = '';
    }

    if ($arrInfoFactura['visualizarFormaPago'] == 1){
        if($arrInfoCliente["muestraFormaPagoFactura"] == true){
            //$html .= '<td style="width:25%">';
            $htmlFooter = '<table>';
            $htmlFooter .= '<tr>';
            $htmlFooter .= '<td style="width: 50%; background-color:'.$fondoTitulos.';color:'.$colorTextoTitulo.'; text-align:center;border:0.7px #E6E7E8 solid;"> FORMA DE PAGO </td>';
            $htmlFooter .= '<td style="width: 50%; background-color:'.$fondoTitulos.';color:'.$colorTextoTitulo.'; text-align:center;border:0.7px #E6E7E8 solid;"> VALOR </td>';
            $htmlFooter .= '</tr>';

            $contador = 0;
            while ($contador < count($arrInfoFormasPago)) {
                $htmlFooter.='<tr>';
                $htmlFooter .= '<td style="background-color:'.$fondoContenido.';border:0.7px #E6E7E8 solid;color:' . $colorTexto . ';text-align:center;text-align:left;">' . $arrInfoFormasPago[$contador]["formaPago"] . '</td>';
                $htmlFooter .= '<td style="background-color:'.$fondoContenido.';border:0.7px #E6E7E8 solid;color:' . $colorTexto . ';text-align:center;text-align:right;">' . number_format($arrInfoFormasPago[$contador]["valor"], 0, '', '.') . '</td>';
                $contador++;
                $htmlFooter .='</tr>';
            }
            $htmlFooter .= '</table>';
            $htmlFooter .= '<br><br>';
        }
    }
    //Letra del total a pagar
    $letraTotalPagar = obtenerTextoNumero($valorTotalPagar);

    //Se genera el footer
    $htmlFooter .= '<table>';
    $htmlFooter .= '<tr>';
    $htmlFooter .= '<td style="background-color:' . $fondoTitulos . ';color:' . $colorTextoTitulo . '; text-align:center;border:0.7px #E6E7E8 solid;"> IMPUESTO </td>';
    $htmlFooter .= '<td style="background-color:' . $fondoTitulos . ';color:' . $colorTextoTitulo . '; text-align:center;border:0.7px #E6E7E8 solid;"> VR. BASE </td>';
    $htmlFooter .= '<td style="background-color:' . $fondoTitulos . ';color:' . $colorTextoTitulo . '; text-align:center;border:0.7px #E6E7E8 solid;"> VR. IMPTO </td>';
    $htmlFooter .= '</tr>';

    $contador = 0;
    while ($contador < count($arrInfoImpuestos)) {

        $htmlFooter.='<tr>';
        $htmlFooter .= '<td style="background-color:' . $fondoContenido . ';border:0.7px #E6E7E8 solid;color:' . $colorTexto . ';">' . $arrInfoImpuestos[$contador]["impuesto"] . '</td>';
        $htmlFooter .= '<td style="background-color:' . $fondoContenido . ';border:0.7px #E6E7E8 solid;color:' . $colorTexto . ';text-align:right;">' . number_format($arrInfoImpuestos[$contador]["totalBase"], 0, '', '.') . '</td>';
        $htmlFooter .= '<td style="background-color:' . $fondoContenido . ';border:0.7px #E6E7E8 solid;color:' . $colorTexto . ';text-align:right;">' . number_format($arrInfoImpuestos[$contador]["totalImpuesto"], 0, '', '.') . '</td>';
        $htmlFooter .='</tr>';

        $contador++;
    }
    $htmlFooter .= '</table>';

    $htmlFooter .= '<br>';
    $htmlFooter .= '<hr style="height: .7px;">';
    $htmlFooter .= '<table style="width:100%;border-collapse: collapse;">';
    $htmlFooter .= '<tr>';
    $htmlFooter .= '<td style="width:80%;color:' . $colorTexto . ';"><b>SON </b> ' . $letraTotalPagar . ' PESOS MCTE </td>';
    $htmlFooter .= '<td style="text-align:right;width:10%;color:' . $colorTexto . ';"> <b>TOTAL</b></td>';
    $htmlFooter .= '<td style="text-align:right;width:10%;color:' . $colorTexto . ';"> $' . number_format($valorTotalPagar, 0, '', '.') . '</td>';
    $htmlFooter .= '</tr>';
    $htmlFooter .= '</table>';

    //Resolución DIAN
    $htmlFooter .= '<br><br>';
    $htmlFooter .= '<table style="width:100%;border-collapse: collapse;">';
    $htmlFooter .= '<tr>';

    $htmlFooter .= '<td style="width: 40%;">';
    $htmlFooter .= '<table style="width:100%;border-collapse: collapse;">';
    $htmlFooter .= '<tr>';
    $htmlFooter .= '<td style="font-size:8px;text-align:center;width:100%;color:' . $colorTexto . ';">REGIMEN ' . strtoupper($arrInfoEmpresa["tipoRegimen"]) . '</td>';
    $htmlFooter .= '</tr>';
    
    if ($arrInfoEmpresa['factura'] == 1){
        $htmlFooter .= '<tr>';
        $htmlFooter .= '<td style="font-size:8px;text-align:center;width:100%;color:' . $colorTexto . ';">ACTIVIDAD ECONÓMICA 6202, 6201, 4651</td>'; //PARAMETRIZAR
        $htmlFooter .= '</tr>';
        $htmlFooter .= '<tr>';
        $htmlFooter .= '<td style="font-size:8px;text-align:center;width:100%;color:' . $colorTexto . ';">NO SOMOS GRANDES CONTRIBUYENTES</td>'; //PARAMETRIZAR
        $htmlFooter .= '</tr>';
        $htmlFooter .= '<tr>';
        $htmlFooter .= '<td style="font-size:8px;text-align:center;width:100%;color:' . $colorTexto . ';">RESOLUCIÓN DIAN: ' . $arrInfoCajero["numeroResolucion"] . '</td>';
        $htmlFooter .= '</tr>';
        $htmlFooter .= '<tr>';
        $htmlFooter .= '<td style="font-size:8px;text-align:center;width:100%;color:' . $colorTexto . ';">FECHA DE RESOLUCIÓN: ' . $arrInfoCajero["fechaExpedicionResolucion"] . '</td>';
        $htmlFooter .= '</tr>';
    
        $htmlFooter .= '<tr>';
        $arrInfoCajero["prefijo"] = trim($arrInfoCajero["prefijo"]);
        if ($arrInfoCajero["prefijo"] != null && $arrInfoCajero["prefijo"] != '' && $arrInfoCajero["prefijo"] != 'null') {
            $htmlFooter .= '<td style="font-size:8px;text-align:center;width:100%;color:' . $colorTexto . ';">PREFIJO: ' . $arrInfoCajero["prefijo"] . ' NUMERACIÓN DESDE 00' . $arrInfoCajero["numeroMinimo"] . ' HASTA ' . $arrInfoCajero["numeroMaximo"] . '</td>';
        } else {
            $htmlFooter .= '<td style="font-size:8px;text-align:center;width:100%;color:' . $colorTexto . ';">NUMERACIÓN DESDE 00' . $arrInfoCajero["numeroMinimo"] . ' HASTA ' . $arrInfoCajero["numeroMaximo"] . '</td>';
        }
        $htmlFooter .= '</tr>';
    }
    
    $htmlFooter .= '<tr>';
    $htmlFooter .= '<td style="font-size:8px;text-align:center;width:100%;color:' . $colorTexto . ';">FACTURA IMPRESA POR COMPUTADOR</td>';
    $htmlFooter .= '</tr>';
    $htmlFooter .= '</table>';
    $htmlFooter .= '</td>';
    
    if($arrInfoCliente["muestraFirmasFactura"] == true){
        $htmlFooter .= '<td style="width: 30%;">';
        $htmlFooter .= '<table style="width:100%;border-collapse: collapse;">';
        $htmlFooter .= '<tr><td>&nbsp;</td></tr>';
        $htmlFooter .= '<tr><td>&nbsp;</td></tr>';
        $htmlFooter .= '<tr>';
        $htmlFooter .= '<td style="text-align:left;width:100%;">__________________________</td>';
        $htmlFooter .= '</tr>';
        $htmlFooter .= '<tr>';
        $htmlFooter .= '<td style="font-size:8px;text-align:left;width:100%;color:' . $colorTexto . ';">FIRMA Y SELLO COMPRADOR</td>';
        $htmlFooter .= '</tr>';
        //$htmlFooter .= '<tr><td>&nbsp;</td></tr>';
        //$htmlFooter .= '<tr>';
        //$htmlFooter .= '<td style="text-align:left;width:100%;">C.C. ________________</td>';
        //$htmlFooter .= '</tr>';
        $htmlFooter .= '</table>';
        $htmlFooter .= '</td>';

        $htmlFooter .= '<td style="width: 30%;">';
        $htmlFooter .= '<table style="width:100%;border-collapse: collapse;">';
        $htmlFooter .= '<tr><td>&nbsp;</td></tr>';
        $htmlFooter .= '<tr><td>&nbsp;</td></tr>';
        $htmlFooter .= '<tr>';
        $htmlFooter .= '<td style="text-align:left;width:100%;">__________________________</td>';
        $htmlFooter .= '</tr>';
        $htmlFooter .= '<tr>';
        $htmlFooter .= '<td style="font-size:8px;text-align:left;width:100%;color:' . $colorTexto . ';">FIRMA Y SELLO VENDEDOR</td>';
        $htmlFooter .= '</tr>';
        //$htmlFooter .= '<tr><td>&nbsp;</td></tr>';
        //$htmlFooter .= '<tr>';
        //$htmlFooter .= '<td style="text-align:left;width:100%;">C.C. ________________</td>';
        //$htmlFooter .= '</tr>';
        $htmlFooter .= '</table>';
        $htmlFooter .= '</td>';
    }

    $htmlFooter .= '</tr>';
    $htmlFooter .= '</table>';


    //Nombre software
    $htmlFooter .= '<br>';
    $htmlFooter .= '<hr style="height: .3px;">';
    $htmlFooter .= '<table style="width:100%;border-collapse: collapse;">';
    $htmlFooter .= '<tr><td>&nbsp;</td></tr>';
    $htmlFooter .= '<tr><td style="font-size:7px;text-align:center;">Esta factura se asimila a una letra de cambio para todos los efectos legales, artículo No. 774 del Código de Comercio</td></tr>';
    $htmlFooter .= '<tr><td style="text-align:center;font-size:7px">El cliente se obliga incondicionalmente a pagar a la orden de '.$arrInfoEmpresa["empresa"].', el precio en la forma aquí pactada, que se efectuará en Neiva, Huila. En caso de mora en el pago de
esta obligación, el cliente pagará un interés moratorio a la tasa máxima legal permitida por la legislación Colombiana, pero el hecho de que se causen intereses no priva a '.$arrInfoEmpresa["empresa"].' de ejercer las acciones
judiciales o extrajudiciales que juzgue convenientes para obtener el pago de las sumas vencidas.</td></tr>';
    $htmlFooter .= '<tr><td>&nbsp;</td></tr>';
    //$htmlFooter .= '<td style="text-align:center;width:100%;">'. obtenerNombreSoftware() .'</td>';
    $htmlFooter .= '<tr>';
    $htmlFooter .= '<td style="width:50%;"> Desarrollado por: </td>';
    $htmlFooter .= '<td colspan="7"> Nuestros productos: </td>';
    $htmlFooter .= '</tr>';
    
    $htmlFooter .= '<tr>';
    $htmlFooter .= '<td valign="middle" style="width:50%;"><img src="../imagenes/logo_iss.png"></td>';
    $htmlFooter .= '<td valign="middle" style="font-size:12px;width:50%;">PRODUCTOS QUÍMICOS INDUSTRIALES, ENVASES Y FRAGANCIAS</td>';
    /*
    $htmlFooter .= '<td style="width:7%;"><img src="../imagenes/productos/Horus 50x50.fw.png"></td>';
    $htmlFooter .= '<td style="width:7%;"><img src="../imagenes/productos/Faiho 50x50.fw.png"></td>';
    $htmlFooter .= '<td style="width:7%;"><img src="../imagenes/productos/LawyersCases 50x50.fw.png"></td>';
    $htmlFooter .= '<td style="width:7%;"><img src="../imagenes/productos/LogoFastServices 50x50.fw.png"></td>';
    $htmlFooter .= '<td style="width:7%;"><img src="../imagenes/productos/SendMessage 50x50.fw.png"></td>';
    $htmlFooter .= '<td style="width:7%;"><img src="../imagenes/productos/LogoR 50x50.fw.png"></td>';
    $htmlFooter .= '<td style="width:7%;"><img src="../imagenes/productos/AllParking 50x50.fw.png"></td>';* 
     */
    $htmlFooter .= '</tr>';
    $htmlFooter .= '</table>';
    /*
    $htmlFooter .= '<table style="width:100%;">';
    $htmlFooter .= '<tr>';
    $htmlFooter .= '<td style="width:20%;"></td>';
    $htmlFooter .= '<td style="width:60%;">';
    $htmlFooter .= '<table style="width:100%;">';
    $htmlFooter .= '<tr><td><br></td></tr>';
    $htmlFooter .= '<tr>';
    $htmlFooter .= '<td style="width:33%;"><img  src="../imagenes/RedesSociales/Facebook.png">ingesoftwareTIC</td>';
    $htmlFooter .= '<td style="width:33%;"><img  src="../imagenes/RedesSociales/GooglePlus.png">ingesoftwareTIC</td>';
    $htmlFooter .= '<td style="width:33%;"><img  src="../imagenes/RedesSociales/Twitter.png">@ingesoftwareTIC</td>';
    $htmlFooter .= '</tr>';
    $htmlFooter .= '<tr><td style="width:100%;text-align:center;">www.ingesoftware.com.co</td></tr>';
    $htmlFooter .= '</table>';
    $htmlFooter .= '</td>';
    $htmlFooter .= '<td style="width:20%;"></td>';
    $htmlFooter .= '</tr>';
    $htmlFooter .= '</table>';
    */
    
    $pdf = new mipdf(PDF_PAGE_ORIENTATION, 'mm', 'Letter', true, 'UTF-8', false);
    $pdf->setRutaFondoPdf($fondoPdf);
    $pdf->setTamanioFuente($tamanioFuente);
    $pdf->setHtmlHeader($htmlHead);
    $pdf->setHtmlFooter($htmlFooter);
    $pdf->SetFont('helvetica', '', $tamanioFuente, '', true);

    for ($i = 1; $i <= $numPags; $i++) {
        $pdf->AddPage();
        $pdf->writeHTML("<br><br><br><br><br><br><br><br><br><br>" . $arrHtmlBody[$i], true, false, true, false);
        $pdf->lastPage();
    }

    $pdf->Output($retorno["ruta"], 'F');
} catch (Exception $ex) {
    $retorno["exito"] = 0;
    $retorno["mensaje"] = $ex->getMessage();
}
echo json_encode($retorno);
?>