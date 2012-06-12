<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Vika
 * Date: 04.06.12
 * Time: 0:34
 */
require_once APPLICATION_PATH . '/model/LifeHelper.php';

/**
 * @return Life|null
 */
function getLife($id)
{
    // Try to get from session.
    $life = getLifeFromSession($id);

    if (!$life) {
        // Not found in session. Try to get from DB.
        $life = LifeHelper::loadFromDb($id);

        if ($life) {
            // Store to session.
            saveLifeToSession($life);
        }
    }

    return $life;
}

/**
 * @return Life|null
 */
function getLifeFromSession($id)
{
    if (!isset($_SESSION[Consts::SNAMESPACE])) {
        return null;
    }

    $session = &$_SESSION[Consts::SNAMESPACE];

    if (!empty($session[$id])) {
        // Found.
        $life = $session[$id];
        assert($life instanceof Life);

        return $life;
    }

    return null;
}

function saveLifeToSession(Life $life)
{
    if (!isset($_SESSION[Consts::SNAMESPACE])) {
        $_SESSION[Consts::SNAMESPACE] = array();
    }

    $session = &$_SESSION[Consts::SNAMESPACE];
    $session[$life->getId()] = $life;
}
