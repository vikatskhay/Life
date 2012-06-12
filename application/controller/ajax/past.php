<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Vika
 * Date: 10.06.12
 * Time: 19:35
 */
require_once APPLICATION_PATH . '/controller/_lifeStorage.php';

if (empty($_POST['id']) || empty($_POST{'it'})) {
    // Parameters missing.
    return;
}

$id     = $_POST['id'];
$iter   = $_POST['it'];

// Try to get Life.
$life = getLife($id);
if (!$life) {
    // Life with such id not found.
    return;
}

try {
    // Get past generation.
    $pastGen = $life->getPastGeneration($iter);

    if (!$pastGen) {
        // Such past generation not found.
        return;
    }


    // Output response.
    $response = new stdClass;
    $response->bitmap   = $pastGen->bitmap;
    $response->tweaked  = $pastGen->tweaked;

    echo json_encode($response);
} catch (Exception $e) {
    Logger::err($e->getCode() . '; ' . $e->getMessage() . '; ' . $e->getTraceAsString());
}

