<?php

namespace App\Http\Controllers;

use App\Models\Restaurante;
use App\Models\CertificadoDigital;
use App\Models\SerieFacturacion;
use App\Services\VeriFactuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Crypt;

class FiscalController extends Controller
{
    protected VeriFactuService $veriFactuService;

    public function __construct(VeriFactuService $veriFactuService)
    {
        $this->veriFactuService = $veriFactuService;
    }

    /**
     * Actualizar credenciales VeriFactu API
     */
    public function updateCredenciales(Restaurante $restaurante, Request $request)
    {
        $validated = $request->validate([
            'verifactu_api_username' => ['required', 'string', 'max:9'],
            'verifactu_api_key' => ['nullable', 'string', 'min:20'],
        ]);

        // Log para debugging
        \Log::info('Guardando credenciales VeriFacti', [
            'restaurante_id' => $restaurante->id,
            'username' => $validated['verifactu_api_username'],
            'api_key_length' => !empty($validated['verifactu_api_key']) ? strlen($validated['verifactu_api_key']) : 0,
        ]);

        // Solo actualizar API key si se proporciona
        $data = ['verifactu_api_username' => $validated['verifactu_api_username']];

        if (!empty($validated['verifactu_api_key'])) {
            // El setter del modelo se encarga de encriptar
            $data['verifactu_api_key'] = trim($validated['verifactu_api_key']);
        }

        $restaurante->update($data);

        // Refrescar modelo para asegurar que tenemos los valores actualizados
        $restaurante->refresh();

        // Log después de guardar
        \Log::info('Credenciales guardadas', [
            'restaurante_id' => $restaurante->id,
            'tiene_credenciales' => $restaurante->tieneCredencialesVeriFactu(),
            'username_saved' => $restaurante->verifactu_api_username,
            'encrypted_key_exists' => !empty($restaurante->verifactu_api_key_encrypted),
        ]);

        // Verificar si se guardaron correctamente
        if ($restaurante->tieneCredencialesVeriFactu()) {
            $mensaje = 'Credenciales de VeriFactu guardadas correctamente. ✅';
        } else {
            // Diagnosticar por qué no se guardaron
            $username_ok = !empty($restaurante->verifactu_api_username);
            $key_ok = !empty($restaurante->verifactu_api_key_encrypted);

            $detalles = [];
            if (!$username_ok) $detalles[] = 'Falta usuario';
            if (!$key_ok) $detalles[] = 'Falta API Key';

            $mensaje = 'Usuario guardado pero faltan datos. ' . implode(', ', $detalles) . '. Asegúrate de llenar ambos campos.';
        }

        return back()
            ->with('ok', $mensaje)
            ->with('active_tab', 'fiscal');
    }

    /**
     * Actualizar datos fiscales del restaurante
     */
    public function update(Restaurante $restaurante, Request $request)
    {
        // Si solo viene facturacion_automatica, es una actualización de configuración
        if ($request->has('facturacion_automatica') && !$request->has('razon_social')) {
            $restaurante->update([
                'facturacion_automatica' => $request->boolean('facturacion_automatica'),
            ]);

            return back()
                ->with('ok', 'Configuración de facturación automática actualizada correctamente.')
                ->with('active_tab', 'fiscal');
        }

        $validated = $request->validate([
            'razon_social' => ['required', 'string', 'max:255'],
            'nombre_comercial' => ['nullable', 'string', 'max:255'],
            'nif' => ['required', 'string', 'size:9'],
            'direccion_fiscal' => ['required', 'string', 'max:255'],
            'municipio' => ['required', 'string', 'max:100'],
            'provincia' => ['required', 'string', 'max:100'],
            'codigo_postal' => ['required', 'string', 'max:10'],
            'pais' => ['required', 'string', 'max:100'],
            'regimen_iva' => ['required', 'in:general,simplificado,criterio_caja'],
            'epigrafe_iae' => ['nullable', 'string', 'max:20'],
            'email_fiscal' => ['nullable', 'email', 'max:255'],
            'telefono_fiscal' => ['nullable', 'string', 'max:20'],
        ]);

        // Validar NIF
        $nifValidacion = $this->veriFactuService->validarNIF($validated['nif']);
        if (!$nifValidacion['valido']) {
            return back()->withErrors(['nif' => $nifValidacion['mensaje']])->withInput();
        }

        // Actualizar datos fiscales
        $restaurante->update($validated);

        // Si tiene certificado y serie, intentar habilitar
        if ($restaurante->datosFiscalesCompletos() &&
            $restaurante->certificadoActivo()->exists() &&
            $restaurante->seriePrincipal()->exists()) {
            $restaurante->habilitarFiscal();
        }

        return back()
            ->with('ok', 'Datos fiscales actualizados correctamente.')
            ->with('active_tab', 'fiscal');
    }

    /**
     * Subir certificado digital
     */
    public function uploadCertificado(Restaurante $restaurante, Request $request)
    {
        $request->validate([
            'certificado' => ['required', 'file', 'max:5120'], // 5MB
            'password_certificado' => ['required', 'string'],
        ]);

        $archivo = $request->file('certificado');
        $password = $request->password_certificado;

        // Validar extensión manualmente (los tipos MIME de .p12/.pfx varían por sistema)
        $extension = strtolower($archivo->getClientOriginalExtension());
        if (!in_array($extension, ['p12', 'pfx'])) {
            return back()
                ->withErrors(['certificado' => 'El archivo debe ser un certificado digital (.p12 o .pfx)'])
                ->withInput()
                ->with('active_tab', 'fiscal');
        }

        // Validar certificado
        $validacion = $this->veriFactuService->validarCertificadoDigital(
            $archivo->getPathname(),
            $password
        );

        if (!$validacion['valido']) {
            $errores = implode(' ', $validacion['errores']);
            return back()
                ->withErrors(['certificado' => 'Certificado no válido: ' . $errores])
                ->with('active_tab', 'fiscal');
        }

        $detalles = $validacion['detalles'];

        // Verificar que el NIF del certificado coincide con el NIF del restaurante
        if (!empty($restaurante->nif) && !empty($detalles['nif'])) {
            if (strtoupper($restaurante->nif) !== strtoupper($detalles['nif'])) {
                return back()
                    ->withErrors(['certificado' => 'El NIF del certificado (' . $detalles['nif'] . ') no coincide con el NIF del restaurante (' . $restaurante->nif . ')'])
                    ->with('active_tab', 'fiscal');
            }
        }

        // Guardar certificado en storage privado
        $directorioBase = storage_path('app/certificados');
        $directorioRestaurante = $directorioBase . '/' . $restaurante->id;
        File::ensureDirectoryExists($directorioRestaurante, 0700, true);

        $nombreArchivo = 'cert_' . time() . '.' . $archivo->getClientOriginalExtension();
        $rutaCompleta = $directorioRestaurante . '/' . $nombreArchivo;
        $archivo->move($directorioRestaurante, $nombreArchivo);

        // Desactivar certificados anteriores
        CertificadoDigital::where('restaurante_id', $restaurante->id)
            ->update(['activo' => false]);

        // Crear registro del certificado
        $certificado = CertificadoDigital::create([
            'restaurante_id' => $restaurante->id,
            'nombre_archivo_original' => $archivo->getClientOriginalName(),
            'ruta_archivo' => $rutaCompleta,
            'password_encriptado' => Crypt::encryptString($password),
            'nif_certificado' => $detalles['nif'] ?? '',
            'titular_certificado' => $detalles['titular'] ?? '',
            'fecha_expedicion' => $detalles['fecha_expedicion'] ?? null,
            'fecha_caducidad' => $detalles['fecha_caducidad'],
            'valido' => true,
            'detalles_validacion' => $detalles,
            'activo' => true,
        ]);

        // Si no tiene serie de facturación, crear una por defecto
        if (!$restaurante->seriePrincipal()->exists()) {
            $serieDefecto = $this->veriFactuService->generarSerieDefecto($restaurante->id);
            SerieFacturacion::create($serieDefecto);
        }

        // Si tiene todos los datos, habilitar
        if ($restaurante->datosFiscalesCompletos()) {
            $restaurante->habilitarFiscal();
        }

        return back()
            ->with('ok', 'Certificado digital subido y validado correctamente.')
            ->with('active_tab', 'fiscal');
    }

    /**
     * Habilitar facturación VeriFactu
     */
    public function habilitar(Restaurante $restaurante)
    {
        if (!$restaurante->datosFiscalesCompletos()) {
            return back()->withErrors(['general' => 'Debes completar todos los datos fiscales.'])->with('active_tab', 'fiscal');
        }

        if (!$restaurante->certificadoActivo()->exists()) {
            return back()->withErrors(['general' => 'Debes subir un certificado digital válido.'])->with('active_tab', 'fiscal');
        }

        if (!$restaurante->seriePrincipal()->exists()) {
            return back()->withErrors(['general' => 'Debes configurar al menos una serie de facturación.'])->with('active_tab', 'fiscal');
        }

        $restaurante->habilitarFiscal();

        return back()
            ->with('ok', '¡Facturación VeriFactu habilitada correctamente! Ya puedes emitir facturas electrónicas.')
            ->with('active_tab', 'fiscal');
    }

    /**
     * Deshabilitar facturación VeriFactu
     */
    public function deshabilitar(Restaurante $restaurante)
    {
        $restaurante->deshabilitarFiscal();

        return back()
            ->with('ok', 'Facturación VeriFactu deshabilitada.')
            ->with('active_tab', 'fiscal');
    }

    /**
     * Mostrar formulario para crear serie de facturación
     */
    public function crearSerie(Restaurante $restaurante)
    {
        $anoActual = now()->year;
        return view('fiscal.serie-create', compact('restaurante', 'anoActual'));
    }

    /**
     * Guardar nueva serie de facturación
     */
    public function guardarSerie(Restaurante $restaurante, Request $request)
    {
        $validated = $request->validate([
            'codigo_serie' => ['required', 'string', 'max:20'],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'prefijo' => ['nullable', 'string', 'max:10'],
            'sufijo' => ['nullable', 'string', 'max:10'],
            'digitos' => ['required', 'integer', 'min:4', 'max:10'],
            'tipo' => ['required', 'in:principal,secundaria,rectificativa'],
            'punto_venta' => ['required', 'in:tpv,online,delivery,general'],
            'ano_fiscal' => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'es_principal' => ['boolean'],
        ]);

        // Verificar que el código de serie no existe
        $existe = SerieFacturacion::where('restaurante_id', $restaurante->id)
            ->where('codigo_serie', $validated['codigo_serie'])
            ->exists();

        if ($existe) {
            return back()->withErrors(['codigo_serie' => 'Ya existe una serie con este código.'])->withInput();
        }

        $validated['restaurante_id'] = $restaurante->id;
        $validated['activa'] = true;
        $validated['ultimo_numero'] = 0;
        $validated['numero_inicial'] = 1;

        $serie = SerieFacturacion::create($validated);

        return redirect()
            ->route('settings.edit', $restaurante)
            ->with('ok', 'Serie de facturación creada correctamente.')
            ->with('active_tab', 'fiscal');
    }

    /**
     * Mostrar formulario para editar serie
     */
    public function editarSerie(Restaurante $restaurante, SerieFacturacion $serie)
    {
        if ($serie->restaurante_id !== $restaurante->id) {
            abort(404);
        }

        return view('fiscal.serie-edit', compact('restaurante', 'serie'));
    }

    /**
     * Actualizar serie de facturación
     */
    public function actualizarSerie(Restaurante $restaurante, SerieFacturacion $serie, Request $request)
    {
        if ($serie->restaurante_id !== $restaurante->id) {
            abort(404);
        }

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'activa' => ['boolean'],
            'es_principal' => ['boolean'],
        ]);

        $serie->update($validated);

        return redirect()
            ->route('settings.edit', $restaurante)
            ->with('ok', 'Serie actualizada correctamente.')
            ->with('active_tab', 'fiscal');
    }

    /**
     * Subir Modelo de Representación firmado
     */
    public function uploadModeloRepresentacion(Restaurante $restaurante, Request $request)
    {
        $request->validate([
            'modelo_representacion' => ['required', 'file', 'max:10240', 'mimes:pdf'], // 10MB max, solo PDF
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        $archivo = $request->file('modelo_representacion');

        // Guardar en storage privado
        $directorioBase = storage_path('app/modelos_representacion');
        $directorioRestaurante = $directorioBase . '/' . $restaurante->id;
        File::ensureDirectoryExists($directorioRestaurante, 0700, true);

        $nombreArchivo = 'modelo_representacion_' . time() . '.pdf';
        $rutaCompleta = $directorioRestaurante . '/' . $nombreArchivo;
        $archivo->move($directorioRestaurante, $nombreArchivo);

        // Marcar como firmado
        $restaurante->marcarModeloRepresentacionFirmado($rutaCompleta, $request->observaciones);

        return back()
            ->with('ok', 'Modelo de Representación subido correctamente. Ya puedes emitir facturas.')
            ->with('active_tab', 'fiscal');
    }
}
