<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order\Product;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    // Mostrar todos los productos
    public function index(): mixed
    {
        return Product::all();
    }

    // Crear un nuevo producto
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric|min:0',
            'stockActual' => 'required|integer|min:0',
        ]);

        $producto = Product::create($request->all());

        return response()->json([
            'mensaje' => 'Producto creado correctamente',
            'producto' => $producto
        ], 201);
    }

    // Consultar producto por ID
    public function show($id)
    {
        $producto = Product::find($id);

        if (!$producto) {
            return response()->json(['mensaje' => 'Producto no encontrado'], 404);
        }

        return response()->json($producto);
    }

    // Actualizar producto
    public function update(Request $request, $id)
    {
        $producto = Product::find($id);
        if (!$producto) return response()->json(['mensaje' => 'Producto no encontrado'], 404);

        $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio' => 'sometimes|required|numeric|min:0',
            'stockActual' => 'sometimes|required|integer|min:0',
        ]);

        $producto->update($request->all());

        return response()->json([
            'mensaje' => 'Producto actualizado correctamente',
            'producto' => $producto
        ]);
    }

    // Eliminar un producto
    public function destroy($id)
    {
        $producto = Product::find($id);

        if (!$producto) return response()->json(['mensaje' => 'Producto no encontrado'], 404);

        $producto->delete();

        return response()->json(['mensaje' => 'Producto eliminado correctamente']);
    }
}