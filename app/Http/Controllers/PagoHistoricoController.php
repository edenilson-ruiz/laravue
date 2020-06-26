<?php

namespace App\Http\Controllers;

use App\Laravue\Models\PagoHistoricoComisiones;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PagoHistoricoController extends Controller
{
    public function index() {

        $fecha_actual = date("d-m-Y");
    
        //resto 6 mes
        $fecha_inicial = date("d-m-Y",strtotime($fecha_actual."- 5 month"));
        $periodoIni = date("Ym", strtotime($fecha_inicial));
        $periodoFin = date("Ym");

        $comisiones = PagoHistoricoComisiones::select(
            'periodo_pago'
            ,DB::raw('sum(comision)*1.13 as expectedData')
            ,DB::raw('sum(comision) as actualData')
        )->groupBy('periodo_pago')
         ->whereBetween('periodo_pago',[$periodoIni, $periodoFin])
         ->orderBy('periodo_pago')
         ->get();

        $data = [];

        
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
