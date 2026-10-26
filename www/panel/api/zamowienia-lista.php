<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/src/bootstrap.php';

Auth::requireRole(['admin', 'pracownik']);

$view = ($_GET['view'] ?? '') === 'wszystkie' ? 'wszystkie' : 'aktywne';
$activeStatuses = ['nowe', 'oplacone', 'w_przygotowaniu', 'gotowe', 'w_dostawie'];
$allStatuses    = array_keys(OrderRepository::STATUS_LABELS);
$statuses       = $view === 'wszystkie' ? $allStatuses : $activeStatuses;

// Ta sama zasada co w zamowienia.php: pracownik zawsze tylko swoja strefa.
$scope  = Auth::zoneScope();
$zoneId = $scope === null ? (((int) ($_GET['strefa'] ?? 0)) ?: null) : $scope;

header('Content-Type: text/html; charset=utf-8');
echo OrderRepository::renderOrdersFragment($statuses, $zoneId, Csrf::token());
