<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Intervention\Image\Laravel\Facades\Image;

class ImageService
{
    /**
     * Convierte una imagen subida a formato WebP y la guarda en el destino especificado.
     *
     * @param UploadedFile $file Archivo de imagen subido
     * @param string $destinoAbsoluto Ruta absoluta del directorio destino
     * @param string $nombreBase Nombre base del archivo (ej: 'logo_rest_1_123456')
     * @param int $calidad Calidad de compresión WebP (0-100, por defecto 85)
     * @param int|null $maxAncho Ancho máximo (redimensiona proporcionalmente si se especifica)
     * @return string Nombre del archivo guardado (ej: 'logo_rest_1_123456.webp')
     */
    public function convertirYGuardarWebP(
        UploadedFile $file,
        string $destinoAbsoluto,
        string $nombreBase,
        int $calidad = 85,
        ?int $maxAncho = null
    ): string {
        // Asegurar que existe el directorio
        File::ensureDirectoryExists($destinoAbsoluto, 0755, true);

        // Leer la imagen con Intervention Image
        $imagen = Image::read($file->getRealPath());

        // Redimensionar si se especifica un ancho máximo (manteniendo proporción)
        if ($maxAncho && $imagen->width() > $maxAncho) {
            $imagen->scale(width: $maxAncho);
        }

        // Nombre del archivo con extensión .webp
        $nombreWebp = $nombreBase . '.webp';
        $rutaCompleta = $destinoAbsoluto . DIRECTORY_SEPARATOR . $nombreWebp;

        // Convertir y guardar como WebP
        $imagen->toWebp($calidad)->save($rutaCompleta);

        return $nombreWebp;
    }

    /**
     * Convierte una imagen existente en disco a formato WebP.
     *
     * @param string $rutaOriginal Ruta absoluta de la imagen original
     * @param int $calidad Calidad de compresión WebP (0-100, por defecto 85)
     * @param bool $eliminarOriginal Si se debe eliminar la imagen original después de convertir
     * @return string Ruta absoluta de la nueva imagen WebP
     */
    public function convertirArchivoAWebP(
        string $rutaOriginal,
        int $calidad = 85,
        bool $eliminarOriginal = true
    ): string {
        if (!File::exists($rutaOriginal)) {
            throw new \Exception("El archivo no existe: {$rutaOriginal}");
        }

        // Leer la imagen
        $imagen = Image::read($rutaOriginal);

        // Generar nueva ruta con extensión .webp
        $rutaWebp = preg_replace('/\.[^.]+$/', '.webp', $rutaOriginal);

        // Convertir y guardar
        $imagen->toWebp($calidad)->save($rutaWebp);

        // Eliminar original si se solicita
        if ($eliminarOriginal && $rutaWebp !== $rutaOriginal) {
            @File::delete($rutaOriginal);
        }

        return $rutaWebp;
    }

    /**
     * Elimina una imagen del disco.
     *
     * @param string $rutaAbsoluta Ruta absoluta de la imagen
     * @return bool True si se eliminó correctamente
     */
    public function eliminarImagen(string $rutaAbsoluta): bool
    {
        if (File::exists($rutaAbsoluta)) {
            return @File::delete($rutaAbsoluta);
        }

        return false;
    }

    /**
     * Genera un nombre único para una imagen.
     *
     * @param string $prefijo Prefijo del nombre (ej: 'img_', 'logo_rest_1_')
     * @return string Nombre único sin extensión
     */
    public function generarNombreUnico(string $prefijo = 'img_'): string
    {
        return $prefijo . time() . '_' . uniqid();
    }
}
