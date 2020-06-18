<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlanillaPospagoMasivoResource;
use App\Laravue\Models\PlanillaPospagoMasivo;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PlanillaPospagoMasivoController extends Controller
{
    const ITEM_PER_PAGE = 50;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //return PlanillaPospagoMasivoResource::collection(PlanillaPospagoMasivo::paginate(10));
        $searchParams = $request->all();
        $planillaQuery = PlanillaPospagoMasivo::query();
        $planillaQuery->select(
            'CODIGO_PLANILLA as codigo_planilla',
            'PERIODO_PAGO as periodo_pago',
            'ACREEDOR_ID as acreedor_id',
            'DISTRIBUIDOR as distribuidor',
            DB::raw("'/upload/' + URL_FILE as url_file"),
            DB::raw("CASE WHEN URL_FILE IS NOT NULL THEN 'presentada' ELSE 'pendiente' END status"),
            DB::raw('sum(UNIDAD_TOTALES) as unidad_totales'),
            DB::raw('sum(UNIDADES_APLICAN) as unidades_aplican'),
            DB::raw('round(sum(COMISION),2) as comision'));
        $planillaQuery->groupBy('CODIGO_PLANILLA','PERIODO_PAGO','ACREEDOR_ID','DISTRIBUIDOR', 'URL_FILE');
        // $planillaQuery->orderBy('comision','desc');
        $limit = Arr::get($searchParams, 'limit', static::ITEM_PER_PAGE);
        $keyword = Arr::get($searchParams, 'keyword', '');

        if (!empty($keyword)) {
            $planillaQuery->where('distribuidor', 'LIKE', '%' . $keyword . '%');
        }

        return PlanillaPospagoMasivoResource::collection($planillaQuery->paginate($limit));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => ['required']
            ]
        );

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 403);
        } else {
            $params = $request->all();
            $category = PlanillaPospagoMasivo::create([
                'name' => $params['name'],
                'code' => strtolower($params['name']) . time(), // Just to make sure this value is unique
                'description' => $params['description'],
            ]);

            return new PlanillaPospagoMasivoResource($category);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Laravue\Models\PlanillaPospagoMasivo  $planillaPospagoMasivo
     * @return \Illuminate\Http\Response
     */
    public function show(PlanillaPospagoMasivo $planillaPospagoMasivo)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Laravue\Models\PlanillaPospagoMasivo  $planillaPospagoMasivo
     * @return \Illuminate\Http\Response
     */
    public function edit(PlanillaPospagoMasivo $planillaPospagoMasivo)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Laravue\Models\PlanillaPospagoMasivo  $planillaPospagoMasivo
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        if ($request->hasFile('file')) {
            return $request->all();
        } else {            
            return $request->all();
        }

        /*

        $codigoPlanilla = $params['codigo_planilla'];


        if ($planilla === null) {
            return response()->json(['error' => 'Planilla not found'], 404);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'uploadFile' => ['required']
            ]
        );

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 403);
        } else {
            $path = public_path('upload');
            $files = $params['uploadFile'];
            foreach($files as $file) {
                $name = time();
                $ext = '.pdf'; // $file->getClientOriginalExtension();
                $save_name = $name . '.' . $ext;
                $file->move($path, $save_name);
                $planilla::where('CODIGO_PLANILLA', '=', $codigoPlanilla)
                    ->update([
                        'URL_FILE' => $save_name
                    ]);
            }

        }

        return new PlanillaPospagoMasivoResource($planilla);
        */
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Laravue\Models\PlanillaPospagoMasivo  $planillaPospagoMasivo
     * @return \Illuminate\Http\Response
     */
    public function destroy(PlanillaPospagoMasivo $planillaPospagoMasivo)
    {
        //
    }

    public function fileStore(Request $request) {

        $response = [];

        $params = $request->all();

        $items = (array) json_decode($request->planilla);

        $codigoPlanilla = $items['codigo_planilla']; 

        $planilla = new PlanillaPospagoMasivo();

        if($request->hasFile('file')) {
            $upload_path = public_path('upload');
            $file_name = $request->file->getClientOriginalName();
            $generated_new_name = $codigoPlanilla . '.' . $request->file->getClientOriginalExtension();
            $request->file->move($upload_path, $generated_new_name);
            $planilla::where('CODIGO_PLANILLA', '=', $codigoPlanilla)
                    ->update([
                        'URL_FILE' => $generated_new_name
                    ]);
            $response = [
                'success' => 'You have successfully uploaded "' . $generated_new_name . '"'
            ];
        } else {
            $response = [
                'error' => 'You have a error uploaded "' . $generated_new_name . '"'
            ];
        }
                
        return response()->json(['success' => 'You have successfully uploaded "' . $file_name . '"']);
        
    }
}
