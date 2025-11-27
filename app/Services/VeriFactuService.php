<?php

namespace App\Services;

use Exception;

class VeriFactuService
{
    /**
     * Validar formato de NIF español
     *
     * @param string $nif
     * @return array ['valido' => bool, 'mensaje' => string, 'tipo' => string]
     */
    public function validarNIF(string $nif): array
    {
        $nif = strtoupper(trim($nif));

        // Eliminar espacios y guiones
        $nif = preg_replace('/[\s\-]/', '', $nif);

        // Validar longitud
        if (strlen($nif) !== 9) {
            return [
                'valido' => false,
                'mensaje' => 'El NIF debe tener 9 caracteres',
                'tipo' => null,
            ];
        }

        // Validar DNI (8 dígitos + letra)
        if (preg_match('/^\d{8}[A-Z]$/', $nif)) {
            return $this->validarDNI($nif);
        }

        // Validar NIE (X, Y, Z + 7 dígitos + letra)
        if (preg_match('/^[XYZ]\d{7}[A-Z]$/', $nif)) {
            return $this->validarNIE($nif);
        }

        // Validar CIF (letra + 7 dígitos + dígito de control)
        if (preg_match('/^[A-W][0-9]{7}[0-9A-J]$/', $nif)) {
            return $this->validarCIF($nif);
        }

        return [
            'valido' => false,
            'mensaje' => 'Formato de NIF/NIE/CIF no válido',
            'tipo' => null,
        ];
    }

    /**
     * Validar DNI
     */
    private function validarDNI(string $dni): array
    {
        $letras = 'TRWAGMYFPDXBNJZSQVHLCKE';
        $numero = substr($dni, 0, 8);
        $letra = substr($dni, 8, 1);
        $letraCalculada = $letras[$numero % 23];

        if ($letra === $letraCalculada) {
            return [
                'valido' => true,
                'mensaje' => 'DNI válido',
                'tipo' => 'DNI',
            ];
        }

        return [
            'valido' => false,
            'mensaje' => 'DNI no válido. La letra no coincide.',
            'tipo' => 'DNI',
        ];
    }

    /**
     * Validar NIE
     */
    private function validarNIE(string $nie): array
    {
        $letras = 'TRWAGMYFPDXBNJZSQVHLCKE';

        // Reemplazar primera letra por dígito
        $primeraLetra = substr($nie, 0, 1);
        $numero = match ($primeraLetra) {
            'X' => '0',
            'Y' => '1',
            'Z' => '2',
            default => '',
        };

        $numero .= substr($nie, 1, 7);
        $letra = substr($nie, 8, 1);
        $letraCalculada = $letras[$numero % 23];

        if ($letra === $letraCalculada) {
            return [
                'valido' => true,
                'mensaje' => 'NIE válido',
                'tipo' => 'NIE',
            ];
        }

        return [
            'valido' => false,
            'mensaje' => 'NIE no válido. La letra no coincide.',
            'tipo' => 'NIE',
        ];
    }

    /**
     * Validar CIF
     */
    private function validarCIF(string $cif): array
    {
        $primeraLetra = substr($cif, 0, 1);
        $digitos = substr($cif, 1, 7);
        $control = substr($cif, 8, 1);

        // Calcular suma para el dígito de control
        $suma = 0;
        for ($i = 0; $i < 7; $i++) {
            $digito = (int) $digitos[$i];
            if ($i % 2 === 0) {
                // Posiciones pares: multiplicar por 2 y sumar dígitos
                $doble = $digito * 2;
                $suma += floor($doble / 10) + ($doble % 10);
            } else {
                // Posiciones impares: sumar directamente
                $suma += $digito;
            }
        }

        $unidad = $suma % 10;
        $digitoControl = ($unidad === 0) ? 0 : 10 - $unidad;

        // Algunas letras requieren letra de control en lugar de número
        $letrasConLetra = ['K', 'P', 'Q', 'S', 'N', 'W'];
        $letrasControl = 'JABCDEFGHI';

        if (in_array($primeraLetra, $letrasConLetra)) {
            $controlEsperado = $letrasControl[$digitoControl];
        } else {
            // Puede ser letra o número
            if (is_numeric($control)) {
                $controlEsperado = (string) $digitoControl;
            } else {
                $controlEsperado = $letrasControl[$digitoControl];
            }
        }

        if ($control === $controlEsperado) {
            return [
                'valido' => true,
                'mensaje' => 'CIF válido',
                'tipo' => 'CIF',
            ];
        }

        return [
            'valido' => false,
            'mensaje' => 'CIF no válido. El dígito de control no coincide.',
            'tipo' => 'CIF',
        ];
    }

    /**
     * Validar certificado digital (.p12 / .pfx)
     *
     * @param string $rutaArchivo Ruta del archivo del certificado
     * @param string $password Contraseña del certificado
     * @return array ['valido' => bool, 'detalles' => array, 'errores' => array]
     */
    public function validarCertificadoDigital(string $rutaArchivo, string $password): array
    {
        $errores = [];
        $detalles = [];

        try {
            // Verificar que el archivo existe
            if (!file_exists($rutaArchivo)) {
                return [
                    'valido' => false,
                    'detalles' => [],
                    'errores' => ['El archivo del certificado no existe'],
                ];
            }

            // Leer el contenido del certificado
            $certificadoContenido = file_get_contents($rutaArchivo);

            // Intentar abrir el certificado PKCS12
            $certificadoDatos = [];
            if (!openssl_pkcs12_read($certificadoContenido, $certificadoDatos, $password)) {
                return [
                    'valido' => false,
                    'detalles' => [],
                    'errores' => ['Contraseña incorrecta o certificado corrupto'],
                ];
            }

            // Parsear el certificado X.509
            $certInfo = openssl_x509_parse($certificadoDatos['cert']);

            if (!$certInfo) {
                return [
                    'valido' => false,
                    'detalles' => [],
                    'errores' => ['No se pudo parsear el certificado'],
                ];
            }

            // Extraer información relevante
            $detalles = [
                'titular' => $certInfo['subject']['CN'] ?? $certInfo['subject']['O'] ?? 'Desconocido',
                'emisor' => $certInfo['issuer']['CN'] ?? $certInfo['issuer']['O'] ?? 'Desconocido',
                'fecha_expedicion' => date('Y-m-d', $certInfo['validFrom_time_t']),
                'fecha_caducidad' => date('Y-m-d', $certInfo['validTo_time_t']),
                'serie' => $certInfo['serialNumber'] ?? null,
            ];

            // Extraer NIF del certificado (puede estar en varios campos)
            $nif = $this->extraerNIFDeCertificado($certInfo);
            if ($nif) {
                $detalles['nif'] = $nif;
            } else {
                $errores[] = 'No se pudo extraer el NIF del certificado';
            }

            // Validar fechas
            $ahora = time();
            if ($certInfo['validFrom_time_t'] > $ahora) {
                $errores[] = 'El certificado aún no es válido';
            }

            if ($certInfo['validTo_time_t'] < $ahora) {
                $errores[] = 'El certificado ha caducado';
            }

            // Si hay errores, el certificado no es válido
            $valido = empty($errores);

            return [
                'valido' => $valido,
                'detalles' => $detalles,
                'errores' => $errores,
            ];
        } catch (Exception $e) {
            return [
                'valido' => false,
                'detalles' => [],
                'errores' => ['Error al validar certificado: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Extraer NIF del certificado digital
     */
    private function extraerNIFDeCertificado(array $certInfo): ?string
    {
        // Buscar en campos comunes donde aparece el NIF
        $camposPosibles = [
            $certInfo['subject']['serialNumber'] ?? null,
            $certInfo['subject']['CN'] ?? null,
            $certInfo['subject']['OU'] ?? null,
        ];

        foreach ($camposPosibles as $campo) {
            if (!$campo) {
                continue;
            }

            // Buscar patrón de NIF en el campo
            if (preg_match('/\b([A-Z0-9]{9})\b/', strtoupper($campo), $matches)) {
                $nifCandidato = $matches[1];

                // Validar que sea un NIF válido
                $validacion = $this->validarNIF($nifCandidato);
                if ($validacion['valido']) {
                    return $nifCandidato;
                }
            }
        }

        return null;
    }

    /**
     * Generar serie de facturación por defecto para un restaurante
     *
     * @param int $restauranteId
     * @param int $anoFiscal
     * @return array
     */
    public function generarSerieDefecto(int $restauranteId, ?int $anoFiscal = null): array
    {
        $ano = $anoFiscal ?? now()->year;

        return [
            'restaurante_id' => $restauranteId,
            'codigo_serie' => "FF-{$ano}",
            'nombre' => "Serie Principal {$ano}",
            'descripcion' => "Serie de facturación principal para el año {$ano}",
            'prefijo' => 'FF',
            'sufijo' => (string) $ano,
            'digitos' => 6,
            'tipo' => 'principal',
            'punto_venta' => 'general',
            'activa' => true,
            'es_principal' => true,
            'ano_fiscal' => $ano,
            'ultimo_numero' => 0,
            'numero_inicial' => 1,
        ];
    }
}
