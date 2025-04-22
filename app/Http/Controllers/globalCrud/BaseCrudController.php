<?php

namespace App\Http\Controllers\globalCrud;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PhpParser\Node\Expr\FuncCall;

class BaseCrudController extends Controller
{
    //Propiedades
    protected $model;
    protected $validationRules =[];

    public function index(){
        try {
            return response()->json($this->model::OrderBy('id','desc')->get());
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Registro no encontrado',
                //metodo que envia un mensaje
                'message' => $th->getMessage(),
            ],500);
        }
    }


    public function show($id){
        try {
            //devolver un registro por id
            $record = $this->model::findOrFail($id);
            return response()->json($record);            
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'error' => 'Error resgistro no encontrado',
                'message' => $th->getMessage(), 
            ], 404);
        }
    }

    public function store(Request $request){
        try {
            //devo
            $validateData = $this->validateRequest($request);
            $record = $this->model::create($validateData);
            return response()->json($record,201);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'error' => 'Datos invalidados',
                'message' => $th->getMessage(),
            ],422);
        }
    }

    public function update(Request $request, $id){
        try {
            $validateData = $this->validateRequest($request);
            $record = $this->model::findOrFail($id);
            $record->update($validateData);
            return response()->json($record);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Datos invalidos',
                'message' => $th->getMessage(),
            ],422);            
        }
    }

    public function destroy($id){
        try {
            //code...
            $record = $this->model::findOrFail($id);
            $record->delete();
            return response()->json('Registro Eliminado Exitosamente');
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'error' => 'Registro no encontrado',
                'message' => $th->getMessage(),
            ], 404);
        }
    }


    protected function validateRequest(Request $request){
        if (empty($this->validationRules)) {
            throw ValidationException::withMessages(['error'=>'Reglas de validacion no definidas en el controlador hijo']);
        }
        return $request->validate($this->validationRules);
    }

}
