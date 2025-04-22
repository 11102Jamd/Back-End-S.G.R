<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CrudController extends Controller
{
    public function index($tabla)
    {
        if (!Schema::hasTable($tabla)) {
            abort(404, "Tabla no encontrada.");
        }

        $columnas = Schema::getColumnListing($tabla);
        $datos = DB::table($tabla)->get();

        return response()->json([
            'tabla' => $tabla,
            'columnas' => $columnas,
            'datos' => $datos,
        ]);
    }

    public function store(Request $request, $tabla)
    {
        $data = $request->except('_token');
        DB::table($tabla)->insert($data);

        return response()->json(['mensaje' => 'Insertado correctamente']);
    }

    public function update(Request $request, $tabla, $id)
    {
        $data = $request->except(['_token', '_method']);
        DB::table($tabla)->where('id', $id)->update($data);

        return response()->json(['mensaje' => 'Actualizado correctamente']);
    }

    public function destroy($tabla, $id)
    {
        DB::table($tabla)->where('id', $id)->delete();

        return response()->json(['mensaje' => 'Eliminado correctamente']);
    }

    public function show($tabla, $id)
    {
        $registro = DB::table($tabla)->where('id', $id)->first();

        return response()->json($registro);
    }
}
