<?php
/**
 * Lectura/inspeccion de archivos .wpress (formato All-in-One WP Migration).
 *
 * Formato del header por entrada (4377 bytes), tomado de
 * lib/vendor/servmask/archiver/class-ai1wm-archiver.php del propio plugin:
 *
 *   name    0    255   nombre del archivo
 *   size  255     14   tamano del contenido
 *   mtime 269     12   fecha de modificacion (unix)
 *   prefix 281  4088   ruta (sin barra final)
 *   crc32 4369     8   checksum (opcional, v2)
 *
 * Uso:
 *   php wpress-extract.php <archivo.wpress> --list
 *   php wpress-extract.php <archivo.wpress> --extract <dir_salida> [--only <prefijo>]
 */

if ($argc < 3) {
    fwrite(STDERR, "Uso: php wpress-extract.php <archivo.wpress> --list | --extract <dir> [--only <prefijo>]\n");
    exit(1);
}

$archive = $argv[1];
$mode    = '--list';
$outDir  = null;
$only    = null;

for ($i = 2; $i < $argc; $i++) {
    switch ($argv[$i]) {
        case '--list':
            $mode = '--list';
            break;
        case '--extract':
            $mode = '--extract';
            // el valor puede venir pegado (--extract=/dir) o separado
            if (strpos($argv[$i], '=') !== false) {
                $outDir = substr($argv[$i], strpos($argv[$i], '=') + 1);
            } elseif (isset($argv[$i + 1]) && $argv[$i + 1][0] !== '-') {
                $outDir = $argv[++$i];
            }
            break;
        case '--only':
            if (strpos($argv[$i], '=') !== false) {
                $only = substr($argv[$i], strpos($argv[$i], '=') + 1);
            } elseif (isset($argv[$i + 1])) {
                $only = $argv[++$i];
            }
            break;
    }
}

if ($mode === '--extract' && $outDir === null) {
    fwrite(STDERR, "--extract requiere un directorio de salida\n");
    exit(1);
}

// normalizar prefijo: sin barra final, para que coincida con $relPath
if ($only !== null) {
    $only = rtrim($only, '/');
    if ($only === '.' || $only === './') {
        $only = '';
    }
}

$fh = fopen($archive, 'rb');
if (!$fh) {
    fwrite(STDERR, "No se pudo abrir: $archive\n");
    exit(1);
}

// fin del archivo = bloque EOF de 4377 bytes
fseek($fh, 0, SEEK_END);
$fileSize = ftell($fh);
$end      = $fileSize - 4377;

$entries   = [];
$extracted = 0;
$skipped   = 0;
$errors    = [];

fseek($fh, 0, SEEK_SET);

while (ftell($fh) < $end) {
    $header = fread($fh, 4377);
    if ($header === '' || strlen($header) < 4377) {
        break;
    }

    $name   = rtrim(substr($header, 0, 255), "\0");
    $size   = (int) rtrim(substr($header, 255, 14), "\0");
    $mtime  = (int) rtrim(substr($header, 269, 12), "\0");
    $prefix = rtrim(substr($header, 281, 4088), "\0");
    $crc32  = rtrim(substr($header, 4369, 8), "\0");

    if ($name === '') {
        break;
    }

    $relPath = ($prefix !== '' ? $prefix . '/' : '') . $name;
    // quiar prefijo ./ de archivos en la raiz del archive
    if (strpos($relPath, './') === 0) {
        $relPath = substr($relPath, 2);
    }
    $entries[] = ['path' => $relPath, 'size' => $size, 'crc' => $crc32];

    if ($mode === '--extract') {
        $isRootOnly = ($only === '');
        $matches    = $isRootOnly
            ? (strpos($relPath, '/') === false)          // solo archivos en la raiz
            : (strpos($relPath, $only) === 0);           // por prefijo de ruta
        $skip = !$matches;

        if ($skip) {
            // saltar contenido sin leerlo en memoria
            fseek($fh, $size, SEEK_CUR);
            $skipped++;
            continue;
        }

        $target = $outDir . '/' . $relPath;
        $dir    = dirname($target);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            $errors[] = "No se pudo crear dir: $dir";
            fseek($fh, $size, SEEK_CUR);
            continue;
        }

        $remaining = $size;
        $out       = fopen($target, 'wb');
        if (!$out) {
            $errors[] = "No se pudo abrir para escritura: $target";
            fseek($fh, $size, SEEK_CUR);
            continue;
        }

        while ($remaining > 0) {
            $chunk = fread($fh, min(1048576, $remaining));
            if ($chunk === '' || $chunk === false) {
                $errors[] = "Lectura truncada en: $relPath";
                break;
            }
            fwrite($out, $chunk);
            $remaining -= strlen($chunk);
        }
        fclose($out);

        if ($mtime > 0) {
            @touch($target, $mtime);
        }
        $extracted++;
    } else {
        fseek($fh, $size, SEEK_CUR);
    }
}

fclose($fh);

// ---------- reporte ----------
if ($mode === '--list') {
    echo "Entradas: " . count($entries) . "\n";
    echo "Bytes totales: " . number_format(array_sum(array_column($entries, 'size'))) . "\n\n";

    $groups = [];
    foreach ($entries as $e) {
        $top = explode('/', $e['path'])[0];
        if (!isset($groups[$top])) {
            $groups[$top] = ['n' => 0, 'b' => 0];
        }
        $groups[$top]['n']++;
        $groups[$top]['b'] += $e['size'];
    }
    arsort($groups);
    echo "Por directorio raiz:\n";
    foreach ($groups as $g => $info) {
        printf("  %-30s %7d archivos %14s bytes\n", $g, $info['n'], number_format($info['b']));
    }

    echo "\nArchivos destacados (sql/json/php de config):\n";
    foreach ($entries as $e) {
        if (preg_match('/(database\.sql|package\.json|settings\.json|wp-config\.php|\.sql$)/i', $e['path'])) {
            printf("  %-70s %12s\n", $e['path'], number_format($e['size']));
        }
    }

    echo "\nPrimeras 20 entradas:\n";
    foreach (array_slice($entries, 0, 20) as $e) {
        printf("  %-68s %12s\n", $e['path'], number_format($e['size']));
    }
} else {
    echo "Extraidos: $extracted\n";
    echo "Omitidos:  $skipped\n";
    if ($errors) {
        echo "\nErrores (" . count($errors) . "):\n";
        foreach (array_slice($errors, 0, 20) as $err) {
            echo "  - $err\n";
        }
        exit(2);
    }
}
