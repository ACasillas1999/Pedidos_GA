<?php
$myfile = fopen("Archivos/testfile.txt", "w") or die("No se puede abrir el archivo.");
$txt = "Datos de prueba.\n";
fwrite($myfile, $txt);
fclose($myfile);
echo "El archivo se creó correctamente.";
?>