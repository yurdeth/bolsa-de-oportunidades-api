<?php

namespace App\Http\Controllers;

use App\Models\Empresas;
use App\Models\User;
use App\Rules\PhoneNumberRule;
use Carbon\Carbon;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class EmpresasController extends Controller {
    /**
     * Muestra una lista de todas las empresas registradas.
     *
     * Este método verifica si el usuario autenticado tiene los permisos adecuados para acceder a esta funcionalidad.
     * Solo los usuarios con tipo de usuario 1 (administrador) o 2 (coordinador) pueden utilizar esta ruta.
     * Si el usuario no tiene los permisos necesarios, devuelve un mensaje de error.
     * En caso de éxito, recupera la información de las empresas y la devuelve en una respuesta JSON.
     *
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con los datos de las empresas
     * o un mensaje de error si el usuario no tiene permisos.
     */
    public function index(): JsonResponse {
        if (Auth::user()->id_tipo_usuario != 1 && Auth::user()->id_tipo_usuario != 2) {
            return response()->json([
                'success' => false,
                'message' => 'Ruta no encontrada en este servidor'
            ]);
        }

        $empresas = (new User)->getInfoEmpresa(null);

        return response()->json([
            'message' => 'Empresas recuperadas correctamente',
            'status' => true,
            'data' => $empresas
        ]);
    }

    /**
     * Almacena una nueva empresa en la base de datos.
     *
     * Este método valida los datos proporcionados por el usuario y, si son correctos, crea un nuevo usuario y una nueva empresa.
     * Si los datos no son válidos, devuelve un mensaje de error con los campos que no cumplen con las reglas de validación.
     * En caso de éxito, devuelve un mensaje de éxito y los datos de la empresa creada.
     *
     * @param \Illuminate\Http\Request $request Datos de la empresa a almacenar
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con el resultado de la operación
     */
    public function store(Request $request): JsonResponse {
        $rules = [
            //            'id_usuario' => 'required|integer|exists:usuarios,id',
            'id_sector' => 'required|integer|exists:sectores_industria,id',
            'nombre' => 'required|string|max:200',
            'direccion' => 'string',
            'telefono' => ['string', 'max:20', 'unique:empresas'],
            'sitio_web' => 'string|max:255',
            'descripcion' => 'string',
            'logo_url' => 'required|string',
            'verificada' => 'boolean',
            'email' => ['required', 'string', 'email', 'max:255', 'unique:usuarios'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'password_confirmation' => ['required', 'string', 'min:8', 'max:255', 'same:password'],
        ];

        $messages = [
            'id_sector.required' => 'El campo sector es obligatorio',
            'id_sector.integer' => 'El campo sector debe ser un número entero',
            'id_sector.exists' => 'El sector seleccionado no existe',
            'nombre.required' => 'El campo nombre es obligatorio',
            'nombre.string' => 'El campo nombre debe ser una cadena de texto',
            'nombre.max' => 'El campo nombre debe tener un máximo de 200 caracteres',
            'direccion.string' => 'El campo dirección debe ser una cadena de texto',
            'telefono.string' => 'El campo teléfono debe ser una cadena de texto',
            'telefono.max' => 'El campo teléfono debe tener un máximo de 20 caracteres',
            'telefono.unique' => 'El teléfono ingresado ya está en uso',
            'sitio_web.string' => 'El campo sitio web debe ser una cadena de texto',
            'sitio_web.max' => 'El campo sitio web debe tener un máximo de 255 caracteres',
            'descripcion.string' => 'El campo descripción debe ser una cadena de texto',
            'logo_url.string' => 'El campo logo debe ser una cadena de texto',
            'logo_url.required' => 'El campo logo es obligatorio',
            'verificada.boolean' => 'El campo verificada debe ser un valor booleano',
            'email.required' => 'El campo correo electrónico es obligatorio',
            'email.string' => 'El campo correo electrónico debe ser una cadena de texto',
            'email.email' => 'El correo electrónico ingresado no es válido',
            'email.max' => 'El campo correo electrónico debe tener un máximo de 255 caracteres',
            'email.unique' => 'El correo electrónico ingresado ya está en uso',
            'password.required' => 'El campo contraseña es obligatorio',
            'password.string' => 'El campo contraseña debe ser una cadena de texto',
            'password.min' => 'El campo contraseña debe tener al menos 8 caracteres',
            'password.max' => 'El campo contraseña debe tener un máximo de 255 caracteres',
            'password_confirmation.required' => 'El campo confirmación de contraseña es obligatorio',
            'password_confirmation.string' => 'El campo confirmación de contraseña debe ser una cadena de texto',
            'password_confirmation.min' => 'El campo confirmación de contraseña debe tener al menos 8 caracteres',
            'password_confirmation.max' => 'El campo confirmación de contraseña debe tener un máximo de 255 caracteres',
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

        /* if ($request->hasFile('logo_url')) {
             $path = $request->file('logo_url')->store('public/logos');
             $url = Storage::url($path);
         }*/

        $url = "No Data Was Provided"; // <- Test purpose only
        $telefono = str_starts_with($request->telefono, "+503") ? $request->telefono : "+503 " . $request->telefono;
        $telefono = preg_replace('/(\+503)\s?(\d{4})(\d{4})/', '$1 $2-$3', $telefono);

        $user = DB::table('empresas')
            ->select('telefono')
            ->where('telefono', $telefono)
            ->first();

        if ($user) {
            return response()->json([
                'message' => 'El teléfono ingresado ya está en uso',
                'status' => false,
                'errors' => ['telefono' => ['El teléfono ingresado ya está en uso']]
            ], 400);
        }

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'id_tipo_usuario' => 4,
            'estado_usuario' => true,
            'fecha_registro' => Carbon::now(),
        ]);

        $id_usuario = $user->id;

        $url = "";
        if ($request->has('logo_url')) {
            $url = $request->logo_url;
            // --------------- put img on storage ---------------------
            $extension = explode('/', explode(':', substr($url, 0, strpos($url, ';')))[1])[1];   // .jpg .png .pdf
            $extension = explode('+', $extension) ? explode('+', $extension)[0] : $extension;
            $replace = substr($url, 0, strpos($url, ',') + 1);
            $image = str_replace($replace, '', $url);
            $image = str_replace(' ', '+', $image);
            $imageName = Str::uuid() . '.' . $extension;
            Storage::disk('imagen-empresa')->put($imageName, base64_decode($image));
            $url = Storage::disk('imagen-empresa')->url($imageName);
        }

        $empresa = Empresas::create([
            'id_usuario' => $id_usuario,
            'id_sector' => $request->id_sector,
            'nombre' => $request->nombre,
            'direccion' => $request->direccion,
            'telefono' => $telefono,
            'sitio_web' => $request->sitio_web,
            'descripcion' => $request->descripcion,
            'logo_url' => $url,
            'verificada' => $request->verificada
        ]);
        $tokenResult = $user->createToken('Personal Access Token');

        // Configurar la expiración del token
        $token = $tokenResult->token;
        $token->expires_at = Carbon::now()->addWeeks(1);
        $token->save();

        $data = [
            'empresa_id' => $empresa->id,
            'user' => $user,
            'token' => $tokenResult->accessToken, // Token de acceso
            'token_type' => 'Bearer',
            'expires_at' => $token->expires_at, // Fecha de expiración
        ];

        return response()->json([
            'message' => 'Empresa registrada correctamente',
            'status' => true,
            'data' => $data
        ]);
    }

    /**
     * Muestra la información de una empresa específica.
     *
     * Este método recupera la información de una empresa específica en la base de datos.
     * Si la empresa no existe, devuelve un mensaje de error.
     * En caso de éxito, devuelve la información de la empresa en una respuesta JSON.
     *
     * @param int $id ID de la empresa a mostrar
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con los datos de la empresa
     */
    public function show($id): JsonResponse {
        if (Auth::user()->id_tipo_usuario != 1 && Auth::user()->id_tipo_usuario != 2 && Auth::user()->id != $id) {
            return response()->json([
                'success' => false,
                'message' => 'Ruta no encontrada en este servidor'
            ]);
        }

        $empresa = (new User)->getInfoEmpresa($id);

        if ($empresa->isEmpty()) {
            return response()->json([
                'message' => 'Empresa no encontrada',
                'status' => false
            ], 404);
        }

        return response()->json([
            'message' => 'Empresa recuperada correctamente',
            'status' => true,
            'data' => $empresa
        ]);

    }

    /**
     * Muestra la información de una empresa específica por proyecto.
     *
     * Este método recupera la información de una empresa específica en la base de datos.
     * Si la empresa no existe, devuelve un mensaje de error.
     * En caso de éxito, devuelve la información de la empresa en una respuesta JSON.
     *
     * @param int $id ID de la empresa a mostrar
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con los datos de la empresa
     */
    public function showByProyecto($id) {
        $empresa = Empresas::where('id_usuario', $id)->get();

        if ($empresa->isEmpty()) {
            return response()->json([
                'message' => 'Empresa no encontrada',
                'status' => false
            ], 404);
        }

        return response()->json([
            'message' => 'Empresa recuperada correctamente',
            'status' => true,
            'data' => $empresa
        ]);
    }

    /**
     * Actualiza la información de una empresa específica.
     *
     * Este método valida los datos proporcionados por el usuario y, si son correctos, actualiza la información de la empresa.
     * Si los datos no son válidos, devuelve un mensaje de error con los campos que no cumplen con las reglas de validación.
     * En caso de éxito, devuelve un mensaje de éxito y los datos de la empresa actualizada.
     *
     * @param \Illuminate\Http\Request $request Datos de la empresa a actualizar
     * @param int $id ID de la empresa a actualizar
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con el resultado de la operación
     */
    public function update(Request $request, $id): JsonResponse {
        if (Auth::user()->id != $id) {
            return response()->json([
                'success' => false,
                'message' => 'Ruta no encontrada en este servidor'
            ]);
        }

        $empresa = Empresas::where('id_usuario', $id)->first();

        if (is_null($empresa)) {
            return response()->json([
                'message' => 'Empresa no encontrada',
                'status' => false
            ], 404);
        }

        $rules = [
            'id_usuario' => 'integer|exists:usuarios,id',
            'id_sector' => 'integer|exists:sectores_industria,id',
            'nombre' => 'string|max:200',
            'direccion' => 'string',
            'telefono' => 'string|max:20|unique:empresas,telefono,' . $id,
            'sitio_web' => 'string|max:255',
            'descripcion' => 'string',
            'logo_url' => 'string',
            'verificada' => 'boolean'
        ];

        $messages = [
            'id_usuario.integer' => 'El campo id_usuario debe ser un número entero',
            'id_usuario.exists' => 'El usuario seleccionado no existe',
            'id_sector.integer' => 'El campo id_sector debe ser un número entero',
            'id_sector.exists' => 'El sector seleccionado no existe',
            'nombre.string' => 'El campo nombre debe ser una cadena de texto',
            'nombre.max' => 'El campo nombre debe tener un máximo de 200 caracteres',
            'direccion.required' => 'El campo dirección es obligatorio',
            'direccion.string' => 'El campo dirección debe ser una cadena de texto',
            'telefono.string' => 'El campo teléfono debe ser una cadena de texto',
            'telefono.max' => 'El campo teléfono debe tener un máximo de 20 caracteres',
            'telefono.unique' => 'El teléfono ingresado ya está en uso',
            'sitio_web.string' => 'El campo sitio web debe ser una cadena de texto',
            'sitio_web.max' => 'El campo sitio web debe tener un máximo de 255 caracteres',
            'descripcion.required' => 'El campo descripción es obligatorio',
            'descripcion.string' => 'El campo descripción debe ser una cadena de texto',
            'logo_url.string' => 'El campo logo debe ser una cadena de texto',
            'verificada.boolean' => 'El campo verificada debe ser un valor booleano',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'status' => false,
                'errors' => $validator->errors()
            ], 400);
        }

        if ($request->has('descripcion')) {
            $empresa->descripcion = $request->descripcion;
        }

        if ($request->has('logo_url')) {
            if ($empresa->logo_url != null) {
                $nameImg = str_replace("img/imagen-empresa/", "", $empresa->logo_url);
                Storage::disk('imagen-empresa')->delete($nameImg);
            }

            $url = $request->logo_url;
            $extension = explode('/', explode(':', substr($url, 0, strpos($url, ';')))[1])[1];
            $extension = explode('+', $extension) ? explode('+', $extension)[0] : $extension;
            $imagenName = Str::uuid() . '.' . $extension;
            $replace = substr($url, 0, strpos($url, ',') + 1);
            $url = str_replace($replace, '', $url);
            $url = str_replace(' ', '+', $url);
            Storage::disk('imagen-empresa')->put($imagenName, base64_decode($url));
            $empresa->logo_url = Storage::disk('imagen-empresa')->url($imagenName);
        }

        foreach ($request->all() as $key => $value) {
            if ($request->has($key) && $key != 'logo_url') {
                $empresa->$key = $value;
            }
        }

        $empresa->save();

        return response()->json([
            'message' => 'Empresa actualizada correctamente',
            'status' => true,
            'data' => $empresa
        ]);
    }

    /**
     * Elimina una empresa específica de la base de datos.
     *
     * Este método elimina una empresa específica de la base de datos.
     * Si la empresa no existe, devuelve un mensaje de error.
     * En caso de éxito, devuelve un mensaje de éxito.
     *
     * @param int $id ID de la empresa a eliminar
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con el resultado de la operación
     */
    public function destroy($id): JsonResponse {
        if (Auth::user()->id_tipo_usuario != 1 && Auth::user()->id != $id) {
            return response()->json([
                'success' => false,
                'message' => 'Ruta no encontrada en este servidor'
            ]);
        }

        $empresa = User::where('id', $id)->first();

        if (is_null($empresa)) {
            return response()->json([
                'message' => 'Empresa no encontrada',
                'status' => false
            ], 404);
        }

        if (isset($empresa->info_empresa[0]->logo_url)) {
            $nameImg = str_replace("img/imagen-empresa/", "", $empresa->info_empresa[0]->logo_url);
            Storage::disk('imagen-empresa')->delete($nameImg);
        }

        $empresa->delete();

        return response()->json([
            'message' => 'Empresa eliminada correctamente',
            'status' => true
        ]);

    }
}
