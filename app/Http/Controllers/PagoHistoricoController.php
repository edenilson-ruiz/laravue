<?php

namespace App\Http\Controllers;

use App\Laravue\Models\PagoHistoricoComisiones;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PagoHistoricoController extends Controller
{
    public function index() {

        $fecha_actual = date("d-m-Y");

        // resto 6 mes
        $fecha_ini = date("d-m-Y",strtotime($fecha_actual."- 6 month"));
        $fecha_fin = date("d-m-Y",strtotime($fecha_actual."- 1 month"));
        $periodoIni = date("Ym", strtotime($fecha_ini));
        $periodoFin = date("Ym", strtotime($fecha_fin));

        // Se genera consulta por periodo, para el grafico con el historico de comision
        // por periodo de pago
        $comisiones = PagoHistoricoComisiones::select(
            'periodo_pago'
            ,DB::raw('round(sum(comision)*1.13,2) as expectedData')
            ,DB::raw('round(sum(comision),2) as actualData')
        )->groupBy('periodo_pago')
         ->where('tipo_comision','not like','%tiempo%aire%')
         ->whereBetween('periodo_pago',[$periodoIni, $periodoFin])
         ->orderBy('periodo_pago')
         ->get();

        // Se extrae por canal la comision del mes en curso
        $comisionesPorCanal = PagoHistoricoComisiones::select(
            'periodo_pago'
            ,'canal'
            ,DB::raw('sum(cantidad) as cantidad')
            ,DB::raw('round(sum(comision),2) as comision')
        )->groupBy('periodo_pago','canal')
         ->where('periodo_pago','=', $periodoFin)
         ->get();

        $data = [];

        $comisionAliados = 0;
        $comisionDistribuidores = 0;
        $comisionCorporativo = 0;
        $comisionCadenas = 0;
        $comisionSegmentada = 0;

        foreach($comisionesPorCanal as $item) {

            switch ($item->canal) {
                case 'ALIADOS':
                    $comisionAliados = $item->comision;
                    break;
                case 'DISTRIBUIDORES':
                    $comisionDistribuidores = $item->comision;
                    break;
                case 'CORPORATIVO':
                    $comisionCorporativo = $item->comision;
                    break;
                case 'CADENAS':
                    $comisionCadenas = $item->comision;
                    break;
                case 'SEGMENTADA':
                    $comisionSegmentada = $item->comision;
                    break;
                default:
                    # code...
                    break;
            }

        }

        $data['comision_aliados'] = $comisionAliados;
        $data['comision_distribuidores'] = $comisionDistribuidores;
        $data['comision_corporativo'] = $comisionCorporativo;
        $data['comision_cadenas'] = $comisionCadenas;
        $data['comision_segmentada'] = $comisionSegmentada;

        $comisionActual = 0;

        foreach ($comisiones as $row) {

            $periodo = $row->periodo_pago;

            $data['newVisitis']['expectedData'][]  = $row->expectedData;
            $data['newVisitis']['actualData'][]  = $row->actualData;
            $data['messages']['expectedData'][]  = $row->expectedData;
            $data['messages']['actualData'][]  = $row->actualData;
            $data['purchases']['expectedData'][]  = $row->expectedData;
            $data['purchases']['actualData'][]  = $row->actualData;
            $data['shoppings']['expectedData'][]  = $row->expectedData;
            $data['shoppings']['actualData'][]  = $row->actualData;
            $data['periodo_pago'][] = $periodo;

            if ( $periodo == $periodoFin) {
                $comisionActual = $row->actualData;
            }

        }

        $data['comision_actual'] = $comisionActual;

        return $data;
    }
}
