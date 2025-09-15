<?php

namespace App\Http\Controllers;

use App\Http\Controllers\globalCrud\BaseCrudController;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends BaseCrudController
{
    /**
     * Modelo asociado.
     *
     * @var string
     */
    protected $model = User::class;


    /**
     * Reglas de validación para usuarios.
     *
     * @var array<string, string>
     */
    protected $validationRules = [
        'name' => 'required|string|max:50',
        'name2' => 'required|string|max:20',
        'surname' => 'required|string|max:20',
        'surname2' => 'required|string|max:20',
        'email' => 'required|email|unique:users,email',
        'rol' => 'required|string|max:50',
        'password' => 'nullable|string|min:8'
    ];


    /**
     * Actualiza un usuario existente.
     *
     * Modifica la validación del campo `email` para permitir
     * que el mismo usuario mantenga su correo sin conflicto de unicidad.
     *
     * @param \Illuminate\Http\Request $request Datos de la solicitud.
     * @param int $id ID del usuario a actualizar.
     * @return \Illuminate\Http\JsonResponse Usuario actualizado o error de validación.
     */
    public function update(Request $request, $id)
    {
        $this->validationRules['email'] = 'required|email|unique:users,email,' . $id;
        return parent::update($request, $id);
    }


    /**
     * desabilita un usuario en la base de datos pero no lo elimina
     * con el fin dee que si no lo elimina no se eliminen sus registros en cascada
     *
     * @param int $id ID del usuario a inhabilitar.
     * @return \Illuminate\Http\JsonResponse Usuario inhabilitado o error de validación.
     */
    public function disable($id)
    {
        try {
            $user = $this->model::findOrFail($id);
            $user->delete();//Ejecuta softdelete y no un delete como tal
            return response()->json([
                'message' => 'usuario inhabilitado temporalmete',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'error no sep udo encontrar el usuario con ese registro',
                'message' => $th->getMessage()
            ], 404);
        }
    }


    public function enable($id)
    {
        try {
            $user = $this->model::withTrashed()->findOrFail($id);

            if ($user->trashed()) {
                $user->restore();
                return response()->json([
                    'message' => 'Usuario reactivado correctamente',
                    'user_id' => $id
                ], 200);
            }

            return response()->json([
                'message' => 'El usuario ya estaba activo',
                'user_id' => $id
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'No se pudo reactivar el usuario',
                'message' => $th->getMessage()
            ], 404);
        }
    }
}
