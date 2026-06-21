<?php
// debug_path.php
echo "<h1>🔍 DIAGNÓSTICO DE RUTAS</h1>";
echo "<p><strong>Archivo actual:</strong> " . __FILE__ . "</p>";
echo "<p><strong>Ruta del script:</strong> " . $_SERVER['SCRIPT_FILENAME'] . "</p>";
echo "<p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p><strong>URI:</strong> " . $_SERVER['REQUEST_URI'] . "</p>";

echo "<hr><h2>Buscar info-produit.php</h2>";

// Buscar archivos info-produit.php en todo el proyecto
function buscarArchivos($dir, $archivo) {
    $resultados = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getFilename() === $archivo) {
            $resultados[] = $file->getPathname();
        }
    }
    return $resultados;
}

$archivos = buscarArchivos($_SERVER['DOCUMENT_ROOT'], 'info-produit.php');
if (empty($archivos)) {
    echo "<p style='color:orange'>⚠️ No se encontró ningún archivo info-produit.php</p>";
} else {
    echo "<ul>";
    foreach ($archivos as $arch) {
        $esActual = ($arch === __FILE__) ? ' ✅ (ESTE ES EL QUE ESTÁS VIENDO)' : '';
        echo "<li><code>$arch</code>$esActual</li>";
    }
    echo "</ul>";
}
?>