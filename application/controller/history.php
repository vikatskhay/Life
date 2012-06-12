<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Vika
 * Date: 10.06.12
 * Time: 19:00
 */
require_once APPLICATION_PATH . '/controller/_lifeStorage.php';

$template = Template::getInstance();

// Validate parameters.
if (empty($_GET['id'])) {
    // No id provided.
    $template->message = 'No life ID provided.';
    $template->render('error');
    return;
}

$id = $_GET['id'];

// Try to get from session or DB, validate.
$life = getLife($id);
if (!$life) {
    // No life with such ID found.
    $template->message = 'No life with such ID found.';
    $template->render('error');
    return;
}

// Validate iteration.
$iteration = $life->getCurrentIteration();
if ($iteration <= 1) {
    // This life has no past.
    $template->message = 'This life has no past.';
    $template->render('error');
    return;

}


// Get generation #(current - 1).
$iteration--;
$pastGen = $life->getPastGeneration($iteration);

// View.
$template->lifeId       = $life->getId();
$template->iteration    = $iteration;
$template->rows         = $life->getRows();
$template->cols         = $life->getCols();
$template->bitmap       = json_encode($pastGen->bitmap);
$template->tweaked      = json_encode($pastGen->tweaked);

$template->render('history');
