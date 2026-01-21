<?php

require 'vendor/autoload.php';

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;

// Configuración
$connectionString = getenv("AZURE_STORAGE_CONNECTION_STRING");
$containerName = "comprimidos";

$blobClient = BlobRestProxy::createBlobService($connectionString);

// Descargar archivo si se solicita
// if (isset($_GET['download_blob'])) {
//     $blobName = $_GET['download_blob'];
//     try {
//         $blob = $blobClient->getBlob($containerName, $blobName);
//         $content = stream_get_contents($blob->getContentStream());

//         header('Content-Type: application/zip');
//         header('Content-Disposition: attachment; filename="' . basename($blobName) . '"');
//         header('Content-Length: ' . strlen($content));

//         echo $content;
//         exit;
//     } catch (Exception $e) {
//         http_response_code(500);
//         echo "Error al descargar el archivo: " . $e->getMessage();
//         exit;
//     }
// }

// Eliminar archivo si se envió solicitud
if (isset($_GET['delete'])) {
    $blobToDelete = $_GET['delete'];
    try {
        $blobClient->deleteBlob($containerName, $blobToDelete);
        echo "<p style='color:green;'>Archivo $blobToDelete eliminado correctamente</p>";
    } catch (ServiceException $e) {
        echo "<p style='color:red;'>Error al eliminar: " . $e->getMessage() . "</p>";
    }
}

// Subir archivo nuevo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['zipfile'])) {
    $uploadedFile = $_FILES['zipfile'];
    if ($uploadedFile['type'] !== 'application/zip') {
        echo "<p style='color:red;'>Solo se permiten archivos ZIP</p>";
    } else {
        $blobName = basename($uploadedFile['name']);
        $content = fopen($uploadedFile['tmp_name'], 'r');
        try {
            $blobClient->createBlockBlob($containerName, $blobName, $content);
            echo "<p style='color:green;'>Archivo $blobName subido correctamente</p>";
        } catch (ServiceException $e) {
            echo "<p style='color:red;'>Error al subir: " . $e->getMessage() . "</p>";
        }
    }
}

// Listar blobs
try {
    $listOptions = new ListBlobsOptions();
    $blobList = $blobClient->listBlobs($containerName, $listOptions);
    $blobs = $blobList->getBlobs();
} catch (ServiceException $e) {
    die("Error al listar blobs: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestor de archivos ZIP en Azure Blob</title>
</head>

<body>
    <h1>Archivos ZIP en '<?= htmlspecialchars($containerName) ?>'</h1>

    <ul>
        <?php foreach ($blobs as $blob): ?>
            <li>
                <a href="<?= htmlspecialchars($blob->getUrl()) ?>" target="_blank">
                    <?= htmlspecialchars($blob->getName()) ?>
                </a>
                [<a href="?delete=<?= urlencode($blob->getName()) ?>)"
                    onclick="return confirm('Eliminar este archivo?')">Eliminar</a>]
            </li>
        <?php endforeach; ?>
    </ul>

    <h2>Subir nuevo archivo ZIP</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="zipfile" accept=".zip" required>
        <button type="submit">Subir</button>
    </form>
</body>

</html>