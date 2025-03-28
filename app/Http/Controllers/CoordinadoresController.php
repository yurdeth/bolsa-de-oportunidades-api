<?php

namespace App\Http\Controllers;

use App\Models\Coordinadores;
use App\Models\User;
use App\Rules\CoordinadorEmailRule;
use App\Rules\PhoneNumberRule;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CoordinadoresController extends Controller {
    /**
     * Muestra una lista de todos los coordinadores registrados.
     *
     * Este método verifica si el usuario autenticado tiene el tipo de usuario adecuado
     * (administrador) para acceder a esta funcionalidad. Si no tiene los permisos requeridos,
     * devuelve un mensaje de error. En caso contrario, recupera la información de los
     * coordinadores mediante el método `getInfoCoordinador` del modelo `User` y devuelve
     * los datos en una respuesta JSON.
     *
     * @return \Illuminate\Http\JsonResponse Respuesta JSON que contiene los datos de los coordinadores
     * o un mensaje de error si el usuario no tiene permisos.
     */
    public function index(): JsonResponse {
        if (Auth::user()->id_tipo_usuario != 1) {
            return response()->json([
//                'message' => 'No tienes permisos para realizar esta acción',
                'message' => 'Ruta no encontrada en este servidor',
                'status' => false
            ]);
        }

        $coordinadores = (new User)->getInfoCoordinador(null);

        return response()->json([
            'message' => 'Coordinadores recuperados correctamente',
            'status' => true,
            'data' => $coordinadores
        ]);
    }

    /**
     * Almacena un nuevo coordinador en la base de datos.
     *
     * Este método almacena un nuevo coordinador en la base de datos. Antes de guardar el coordinador,
     * se validan los datos proporcionados por el usuario. Si los datos no son válidos, se devuelve un
     * mensaje de error con los detalles de la validación y un código de estado 400. Si el coordinador
     * se guarda correctamente, se devuelve un mensaje de éxito junto con los datos del coordinador y
     * un código de estado 201.
     *
     * @param \Illuminate\Http\Request $request Datos del coordinador a almacenar.
     * @return \Illuminate\Http\JsonResponse Respuesta JSON que indica si el coordinador se guardó correctamente
     * o si hubo un error de validación.
     */
    public function store(Request $request): JsonResponse {
        if (Auth::user()->id_tipo_usuario != 1) {
            return response()->json([
                'message' => 'Ruta no encontrada en este servidor',
                'status' => false
            ]);
        }

        $rules = [
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'id_carrera' => 'required|integer|exists:carreras,id',
            'telefono' => ['string', 'max:20', 'unique:coordinadores', new PhoneNumberRule()],
            'email' => ['required', 'email', 'unique:usuarios', new CoordinadorEmailRule()],
            'password' => 'required|string|min:8',
            'password_confirmation' => 'required|same:password'
        ];

        $messages = [
            'nombres.required' => 'El campo nombres es obligatorio',
            'nombres.string' => 'El campo nombres debe ser una cadena de texto',
            'nombres.max' => 'El campo nombres debe tener un máximo de 100 caracteres',
            'apellidos.required' => 'El campo apellidos es obligatorio',
            'apellidos.string' => 'El campo apellidos debe ser una cadena de texto',
            'apellidos.max' => 'El campo apellidos debe tener un máximo de 100 caracteres',
            'id_carrera.required' => 'El campo carrera es obligatorio',
            'id_carrera.integer' => 'El campo carrera debe ser un número entero',
            'id_carrera.exists' => 'La carrera seleccionada no existe',
            'telefono.string' => 'El campo teléfono debe ser una cadena de texto',
            'telefono.max' => 'El campo teléfono debe tener un máximo de 20 caracteres',
            'telefono.unique' => 'El teléfono ingresado ya está registrado',
            'email.required' => 'El campo correo electrónico es obligatorio',
            'email.email' => 'El campo correo electrónico debe ser una dirección de correo válida',
            'email.unique' => 'El correo electrónico ingresado ya está registrado',
            'password.required' => 'El campo contraseña es obligatorio',
            'password.string' => 'El campo contraseña debe ser una cadena de texto',
            'password.min' => 'El campo contraseña debe tener un mínimo de 8 caracteres',
            'password_confirmation.required' => 'El campo confirmación de contraseña es obligatorio',
            'password_confirmation.same' => 'Las contraseñas no coinciden'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'status' => false,
                'errors' => $validator->errors()
            ], 400);
        }

        $telefono = str_starts_with($request->telefono, "+503") ? $request->telefono : "+503 " . $request->telefono;
        $telefono = preg_replace('/(\+503)\s?(\d{4})(\d{4})/', '$1 $2-$3', $telefono);

        $user = DB::table('coordinadores')
            ->select('telefono')
            ->where('telefono', $telefono)
            ->first();

        if ($user) {
            return response()->json([
                'message' => 'El teléfono ingresado ya está en uso',
                'status' => false
            ], 400);
        }

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'id_tipo_usuario' => 2,
            'estado_usuario' => true,
            'fecha_registro' => Carbon::now(),
        ]);

        $id_usuario = $user->id;

        $coordinador = Coordinadores::create([
            'id_usuario' => $id_usuario,
            'nombres' => $request->nombres,
            'apellidos' => $request->apellidos,
            'id_carrera' => $request->id_carrera,
            'telefono' => $telefono
        ]);

        return response()->json([
            'message' => 'Coordinador creado correctamente',
            'status' => true,
            'data' => $coordinador
        ], 201);
    }

    /**
     * Muestra los datos de un coordinador específico.
     *
     * Este método recupera los datos de un coordinador específico en la base de datos. Si el coordinador
     * no se encuentra, se devuelve un mensaje de error con un código de estado 404. En caso de éxito, se
     * devuelve un mensaje positivo junto con los datos del coordinador.
     *
     * @param int $id Identificador del coordinador a buscar.
     * @return \Illuminate\Http\JsonResponse Respuesta JSON que contiene los datos del coordinador
     * o un mensaje de error si no se encontró el coordinador.
     */
    public function show($id): JsonResponse {
        if (Auth::user()->id_tipo_usuario != 1) {
            return response()->json([
//                'message' => 'No tienes permisos para realizar esta acción',
                'message' => 'Ruta no encontrada en este servidor',
                'status' => false
            ]);
        }

        $coordinador = (new User)->getInfoCoordinador($id);

        if (is_null($coordinador)) {
            return response()->json([
                'message' => 'Coordinador no encontrado',
                'status' => false
            ], 404);
        }

        return response()->json([
            'message' => 'Coordinador recuperado correctamente',
            'status' => true,
            'data' => $coordinador
        ]);
    }

    /**
     * Actualiza los datos de un coordinador específico.
     *
     * Este método actualiza los datos de un coordinador específico en la base de datos. Antes de
     * actualizar el coordinador, se validan los datos proporcionados por el usuario. Si los datos
     * no son válidos, se devuelve un mensaje de error con los detalles de la validación y un código
     * de estado 400. Si el coordinador se actualiza correctamente, se devuelve un mensaje de éxito
     * junto con los datos del coordinador.
     *
     * @param \Illuminate\Http\Request $request Datos del coordinador a actualizar.
     * @param int $id Identificador del coordinador a actualizar.
     * @return \Illuminate\Http\JsonResponse Respuesta JSON que indica si el coordinador se actualizó correctamente
     * o si hubo un error de validación.
     */
    public function update(Request $request, $id): JsonResponse {
        if (Auth::user()->id_tipo_usuario != 1 && Auth::user()->id != $id) {
            return response()->json([
                'message' => 'Ruta no encontrada en este servidor',
                'status' => false
            ]);
        }

        $coordinador = Coordinadores::where('id_usuario', $id)->first();

        if (is_null($coordinador)) {
            return response()->json([
                'message' => 'Coordinador no encontrado',
                'status' => false
            ], 404);
        }

        $rules = [
            'nombres' => 'string|max:100',
            'apellidos' => 'string|max:100',
            'id_departamento' => 'integer|exists:departamento,id',
            'telefono' => ['string', 'max:20', new PhoneNumberRule()],
            'id_carrera' => 'integer|exists:carreras,id',
        ];

        $messages = [
            'nombres.string' => 'El campo nombres debe ser una cadena de texto',
            'nombres.max' => 'El campo nombres debe tener un máximo de 100 caracteres',
            'apellidos.string' => 'El campo apellidos debe ser una cadena de texto',
            'apellidos.max' => 'El campo apellidos debe tener un máximo de 100 caracteres',
            'id_departamento.integer' => 'El campo departamento debe ser un número entero',
            'id_departamento.exists' => 'El departamento seleccionado no existe',
            'telefono.string' => 'El campo teléfono debe ser una cadena de texto',
            'telefono.max' => 'El campo teléfono debe tener un máximo de 20 caracteres',
            'telefono.unique' => 'El teléfono ingresado ya está registrado'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'status' => false,
                'errors' => $validator->errors()
            ], 400);
        }

        $telefono = str_starts_with($request->telefono, "+503") ? $request->telefono : "+503 " . $request->telefono;
        $telefono = preg_replace('/(\+503)\s?(\d{4})(\d{4})/', '$1 $2-$3', $telefono);

        $user = DB::table('coordinadores')
            ->select('telefono')
            ->where('telefono', $telefono)
            ->where('id_usuario', '!=', $id)
            ->first();

        if ($user) {
            return response()->json([
                'message' => 'El teléfono ingresado ya está en uso',
                'status' => false
            ], 400);
        }

        if ($request->has('nombres')) {
            $coordinador->nombres = $request->nombres;
        }

        if ($request->has('apellidos')) {
            $coordinador->apellidos = $request->apellidos;
        }

        if ($request->has('id_carrera')) {
            $coordinador->id_carrera = $request->id_carrera;
        }

        if ($request->has('telefono')) {
            $coordinador->telefono = $telefono;
        }

        if ($request->has('password')) {
            $user = User::find($id);
            $user->password = Hash::make($request->password);
            $user->save();
        }

        $coordinador->save();

        return response()->json([
            'message' => 'Coordinador actualizado correctamente',
            'status' => true,
            'data' => $coordinador
        ]);
    }

    /**
     * Elimina un coordinador específico de la base de datos.
     *
     * Este método elimina un coordinador específico de la base de datos. Si el coordinador no se encuentra,
     * se devuelve un mensaje de error con un código de estado 404. Si el coordinador se elimina correctamente,
     * se devuelve un mensaje de éxito.
     *
     * @param int $id Identificador del coordinador a eliminar.
     * @return \Illuminate\Http\JsonResponse Respuesta JSON que indica si el coordinador se eliminó correctamente
     * o si no se encontró el coordinador.
     */
    public function destroy($id): JsonResponse {
        if (Auth::user()->id != $id && Auth::user()->id_tipo_usuario != 1) {
            return response()->json([
//                'message' => 'No tienes permisos para realizar esta acción',
                'message' => 'Ruta no encontrada en este servidor',
                'status' => false
            ]);
        }

        $user = User::where('id', $id)->first();

        if (is_null($user)) {
            return response()->json([
                'message' => 'Coordinador no encontrado',
                'status' => false
            ], 404);
        }

        $user->delete();

        return response()->json([
            'message' => 'Coordinador eliminado correctamente',
            'status' => true
        ]);
    }
}
