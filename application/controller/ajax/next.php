<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Vika
 * Date: 10.06.12
 * Time: 18:59
 */
require_once APPLICATION_PATH . '/controller/_lifeStorage.php';

if (empty($_POST['id'])) {
    // Parameters missing.
    return;
}

$id = $_POST['id'];

// Try to get Life.
$life = getLife($id);
if (!$life) {
    // Life with such id not found.
    return;
}


try {
    // Next generation.
    $changes = $life->nextGeneration();

    // Store to session.
    saveLifeToSession($life);

    // Output response.
    $response = new stdClass;
    $response->iteration    = $life->getCurrentIteration();
    $response->status       = $life->getStatus();
    $response->changes      = $changes;

    echo json_encode($response);
} catch (Exception $e) {
    Logger::err($e->getCode() . '; ' . $e->getMessage() . '; ' . $e->getTraceAsString());
}

