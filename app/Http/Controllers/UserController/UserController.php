<?php

namespace App\Http\Controllers\UserController;

use App\Http\Controllers\Controller;
use App\Http\Controllers\globalCrud\BaseCrudController;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Controlador para la gestión de usuarios.
 *
 * Extiende de {@see BaseCrudController} para heredar funcionalidad CRUD
 * y adaptarla al modelo `User`.
 *
 */
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
        'name1' => 'required|string|max:50',
        'name2' => 'required|string|max:50',
        'surname1' => 'required|string|max:50',
        'surname2' => 'required|string|max:50',
        'email' => 'required|email|unique:users,email',
        'password' => 'nullable|string|min:8',
        'rol' => 'required|string'
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
}
