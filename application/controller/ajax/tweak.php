<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Vika
 * Date: 10.06.12
 * Time: 19:35
 */
 
require_once APPLICATION_PATH . '/controller/_lifeStorage.php';

if (empty($_POST['id']) || empty($_POST['tweaks'])) {
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
    $tweaks = $_POST['tweaks'];
    $tweaks = json_decode($tweaks);
    $tweaks = (array)$tweaks;
    assert(!empty($tweaks));

    // Tweak.
    $life->tweak($tweaks);

    // Store to session.
    saveLifeToSession($life);
} catch (Exception $e) {
    Logger::err($e->getCode() . '; ' . $e->getMessage() . '; ' . $e->getTraceAsString());
}
