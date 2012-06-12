<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Vika
 * Date: 10.06.12
 * Time: 19:00
 */
require_once APPLICATION_PATH . '/model/LifeHelper.php';
require_once APPLICATION_PATH . '/controller/_lifeStorage.php';

$template = Template::getInstance();

// Check passed parameters.
if (isset($_POST['rows']) &&
    isset($_POST['cols']) &&
    isset($_POST['tweaks'])) {
    // New life.
    $rows   = $_POST['rows'];
    $cols   = $_POST['cols'];
    $tweaks = $_POST['tweaks'];
    $tweaks = json_decode($tweaks);
    $tweaks = (array)$tweaks;

    // Make a bitmap.
    $bitmap = LifeHelper::tweaksToBitmap($rows, $cols, $tweaks);

    if ($bitmap) {
        // New life.
        $life = Life::factory($bitmap);

        // Store to session.
        saveLifeToSession($life);

        // View.
        $template->lifeId       = $life->getId();
        $template->iteration    = 1;
        $template->rows         = $rows;
        $template->cols         = $cols;
        $template->cells        = json_encode($tweaks);

        $template->render('life');

        return;
    }
}

if (!empty($_GET['id'])) {
    // Life id passed.
    $id = $_GET['id'];

    // Try to get from session or DB.
    $life = getLife($id);

    if ($life) {
        // Life found.
        // View.
        $template->lifeId       = $life->getId();
        $template->iteration    = $life->getCurrentIteration();
        $template->rows         = $life->getRows();
        $template->cols         = $life->getCols();
        $template->cells        = json_encode($life->getLiving());

        $template->render('life');
        
        return;
    }
}


// Nothing passed. Prompt user to create a new Life.
$template->render('new');
