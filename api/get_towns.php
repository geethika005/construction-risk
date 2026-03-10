<?php
require_once '../config.php';
require_once '../ml_predictor.php';

header('Content-Type: application/json');

// Get district from query parameter
$district = isset($_GET['district']) ? trim($_GET['district']) : '';

if (empty($district)) {
    echo json_encode(['error' => 'District parameter is required']);
    exit();
}

// Get towns for the district
$predictor = new MLPredictor();
$towns = $predictor->getTownsForDistrict($district);

// Return JSON response
echo json_encode([
    'success' => true,
    'district' => $district,
    'towns' => $towns
]);
?>
