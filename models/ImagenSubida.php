<?php

declare(strict_types=1);

namespace Model;

/** Valida, re-codifica y guarda imágenes subidas. No toca la base de datos. */
class ImagenSubida
{
    public const MAX_BYTES = 2 * 1024 * 1024;
    private const MAX_PIXELES = 12_000_000;
    private const MIMES = ['image/jpeg' => true, 'image/png' => true, 'image/webp' => true];

    /** tipo => [tabla, columna, carpeta, lado máximo en px]. Valores fijos. */
    public const DESTINOS = [
    'equipo_escudo' => ['equipos',    'escudo', 'equipos',    400],
    'equipo_foto'   => ['equipos',    'foto',   'equipos',    800],
    'jugador'       => ['jugadores',  'foto',   'jugadores',  800],
    'cancha'        => ['canchas',    'foto',   'canchas',    800],
    'arbitro'       => ['arbitros',   'foto',   'arbitros',   800],
    'destacado'     => ['destacados', 'imagen', 'destacados', 1600],
];

    /** Borra un archivo solo si la ruta tiene exactamente la forma que genera guardar(). */
    public static function eliminar(?string $ruta): void
    {
        if ($ruta === null || $ruta === '') {
            return;
        }

        $carpetas = array_unique(array_column(self::DESTINOS, 2));
        $patron   = '#^/uploads/(' . implode('|', array_map('preg_quote', $carpetas)) . ')/[a-f0-9]{32}\.(webp|png|jpg)$#';

        if (!preg_match($patron, $ruta)) {
            return;
        }

        $archivo = dirname(__DIR__) . '/public' . $ruta;
        if (is_file($archivo)) {
            @unlink($archivo);
        }
    }

    /** @return array{ruta?: string, error?: string} */
    public static function guardar(array $archivo, string $tipo): array
    {
        if (!isset(self::DESTINOS[$tipo])) {
            return ['error' => 'Tipo de imagen no válido.'];
        }
        if (!extension_loaded('gd')) {
            return ['error' => 'El servidor no puede procesar imágenes (falta GD).'];
        }

        [, , $carpeta, $lado] = self::DESTINOS[$tipo];

        $codigo = (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($codigo !== UPLOAD_ERR_OK) {
            return ['error' => self::mensajeError($codigo)];
        }

        $tmp = (string) ($archivo['tmp_name'] ?? '');
        if (!is_uploaded_file($tmp)) {
            return ['error' => 'No se pudo subir la imagen.'];
        }
        if (filesize($tmp) > self::MAX_BYTES) {
            return ['error' => 'La imagen supera los 2 MB.'];
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        $info = @getimagesize($tmp);
        if (!isset(self::MIMES[$mime]) || $info === false || $info[0] < 1 || $info[1] < 1) {
            return ['error' => 'El archivo tiene que ser una imagen JPG, PNG o WebP.'];
        }
        if ($info[0] * $info[1] > self::MAX_PIXELES) {
            return ['error' => 'La imagen tiene demasiados píxeles.'];
        }

        $origen = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($tmp),
            'image/png'  => @imagecreatefrompng($tmp),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmp) : false,
            default      => false,
        };
        if (!$origen) {
            return ['error' => 'No se pudo leer la imagen.'];
        }

        // La re-codificación descarta el EXIF, así que corregimos la orientación antes.
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif  = @exif_read_data($tmp);
            $giro  = match ((int) ($exif['Orientation'] ?? 1)) { 3 => 180, 6 => -90, 8 => 90, default => 0 };
            $rotada = $giro !== 0 ? imagerotate($origen, $giro, 0) : false;
            if ($rotada) {
                $origen = $rotada;
            }
        }

        $w = imagesx($origen);
        $h = imagesy($origen);
        $escala = min(1, $lado / max($w, $h));
        $nw = max(1, (int) round($w * $escala));
        $nh = max(1, (int) round($h * $escala));

        $destino = imagecreatetruecolor($nw, $nh);
        imagealphablending($destino, false);
        imagesavealpha($destino, true);
        imagefill($destino, 0, 0, imagecolorallocatealpha($destino, 0, 0, 0, 127));
        imagecopyresampled($destino, $origen, 0, 0, 0, 0, $nw, $nh, $w, $h);

        $webp = function_exists('imagewebp') && (imagetypes() & IMG_WEBP);
        $ext  = $webp ? 'webp' : ($mime === 'image/png' ? 'png' : 'jpg');

        $dir = dirname(__DIR__) . '/public/uploads/' . $carpeta;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return ['error' => 'No se pudo crear la carpeta de imágenes.'];
        }

        $nombre = bin2hex(random_bytes(16)) . '.' . $ext;
        $ok = match ($ext) {
            'webp'  => imagewebp($destino, "{$dir}/{$nombre}", 85),
            'png'   => imagepng($destino, "{$dir}/{$nombre}", 6),
            default => imagejpeg($destino, "{$dir}/{$nombre}", 85),
        };

        return $ok
            ? ['ruta' => "/uploads/{$carpeta}/{$nombre}"]
            : ['error' => 'No se pudo guardar la imagen.'];
    }

    private static function mensajeError(int $codigo): string
    {
        return match ($codigo) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'La imagen supera el tamaño permitido (2 MB).',
            UPLOAD_ERR_PARTIAL                        => 'La subida se interrumpió. Probá de nuevo.',
            UPLOAD_ERR_NO_FILE                        => 'No elegiste ninguna imagen.',
            default                                   => 'No se pudo subir la imagen.',
        };
    }
}