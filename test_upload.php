<?php
// Script de prueba para diagnosticar problemas de subida de archivos
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Diagnóstico de rutas y permisos</h2>";

// 1. Información del servidor
echo "<h3>1. Información del servidor</h3>";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script actual (__FILE__): " . __FILE__ . "<br>";
echo "Directorio del script (__DIR__): " . __DIR__ . "<br>";
echo "dirname(__FILE__): " . dirname(__FILE__) . "<br>";
echo "realpath(dirname(__FILE__)): " . realpath(dirname(__FILE__)) . "<br>";

// 2. Verificar directorio Archivos
echo "<h3>2. Directorio Archivos/</h3>";
$uploadDir = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'Archivos' . DIRECTORY_SEPARATOR;
echo "Ruta completa: " . $uploadDir . "<br>";
echo "¿Existe? " . (file_exists($uploadDir) ? "SÍ" : "NO") . "<br>";
echo "¿Es directorio? " . (is_dir($uploadDir) ? "SÍ" : "NO") . "<br>";
echo "¿Es escribible? " . (is_writable($uploadDir) ? "SÍ" : "NO") . "<br>";

// 3. Permisos
if (file_exists($uploadDir)) {
    $perms = fileperms($uploadDir);
    echo "Permisos (octal): " . substr(sprintf('%o', $perms), -4) . "<br>";
}

// 4. Usuario del proceso PHP
echo "<h3>3. Usuario del proceso PHP</h3>";
if (function_exists('posix_getpwuid')) {
    $processUser = posix_getpwuid(posix_geteuid());
    echo "Usuario: " . $processUser['name'] . "<br>";
} else {
    echo "No se puede determinar (posix no disponible)<br>";
}

// 5. Información de PHP
echo "<h3>4. Configuración de PHP para uploads</h3>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "upload_tmp_dir: " . ini_get('upload_tmp_dir') . "<br>";
echo "file_uploads: " . (ini_get('file_uploads') ? "Habilitado" : "Deshabilitado") . "<br>";

// 6. Intentar crear un archivo de prueba
echo "<h3>5. Prueba de escritura</h3>";
$testFile = $uploadDir . 'test_' . time() . '.txt';
$result = @file_put_contents($testFile, 'Test de escritura');
if ($result !== false) {
    echo "✅ Se pudo crear archivo de prueba: " . $testFile . "<br>";
    // Eliminar archivo de prueba
    @unlink($testFile);
    echo "✅ Archivo de prueba eliminado<br>";
} else {
    echo "❌ NO se pudo crear archivo de prueba<br>";
    echo "Error: " . error_get_last()['message'] . "<br>";
}

// 7. Verificar propietario del directorio
echo "<h3>6. Propietario del directorio</h3>";
if (function_exists('posix_getpwuid')) {
    $dirOwner = posix_getpwuid(fileowner($uploadDir));
    echo "Propietario: " . $dirOwner['name'] . "<br>";
    $dirGroup = posix_getgrgid(filegroup($uploadDir));
    echo "Grupo: " . $dirGroup['name'] . "<br>";
}
?>
