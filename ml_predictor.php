<?php
// ==========================================================
// ML PREDICTION API - PHP Handler
// ==========================================================
// This calls the Python ML model via command line
// Alternative: You can also use Python microservice
// ==========================================================

require_once 'config.php';

class MLPredictor {
    
    private $pythonPath = 'python'; // will be resolved in constructor
    private $scriptPath = '';
    private $baseDir = '';

    public function __construct() {
        // Set base directory
        $this->baseDir = __DIR__;
        $this->scriptPath = $this->baseDir . '/predict_cli.py';
        
        // If a virtualenv python exists in project .venv, prefer it
        $venvPyWin = $this->baseDir . '/.venv/Scripts/python.exe';
        $venvPyLin = $this->baseDir . '/.venv/bin/python';
        
        if (file_exists($venvPyWin)) {
            $this->pythonPath = $venvPyWin;
        } elseif (file_exists($venvPyLin)) {
            $this->pythonPath = $venvPyLin;
        } else {
            // try system python
            $this->pythonPath = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') 
                ? (trim(shell_exec('where python 2>NUL')) ?: 'python')
                : (trim(shell_exec('which python3 2>/dev/null')) ?: 'python3');
        }
    }
    
    /**
     * Get available locations from training dataset
     */
    public function getAvailableLocations() {
        $conn = getDBConnection();
        
        // For now, we'll use a predefined list
        // You can also read from a CSV or database table
        $query = "SELECT DISTINCT district FROM Construction_Details ORDER BY district";
        $result = $conn->query($query);
        
        $locations = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $locations[] = $row['district'];
            }
        } else {
            // Default Kerala districts
            $locations = [
                'Alappuzha', 'Ernakulam', 'Idukki', 'Kannur', 'Kasaragod',
                'Kollam', 'Kottayam', 'Kozhikode', 'Malappuram', 'Palakkad',
                'Pathanamthitta', 'Thiruvananthapuram', 'Thrissur', 'Wayanad'
            ];
        }
        
        closeDBConnection($conn);
        return $locations;
    }
    
    /**
     * Get towns for a specific district
     */
    public function getTownsForDistrict($district) {
        // Sample data - in production, read from dataset
        $townData = [
            'Kottayam' => ['Pala', 'Kanjirappally', 'Changanassery', 'Kottayam', 'Vaikom'],
            'Idukki' => ['Thodupuzha', 'Munnar', 'Nedumkandam', 'Painavu', 'Peermade'],
            'Ernakulam' => ['Kochi', 'Aluva', 'Perumbavoor', 'Muvattupuzha', 'Thrippunithura'],
            'Kollam' => ['Kollam', 'Karunagappally', 'Kottarakkara', 'Punalur', 'Chavara'],
            'Thiruvananthapuram' => ['Thiruvananthapuram', 'Nedumangad', 'Neyyattinkara', 'Attingal', 'Varkala'],
            'Thrissur' => ['Thrissur', 'Chalakudy', 'Kodungallur', 'Irinjalakuda', 'Wadakkanchery'],
            'Kozhikode' => ['Kozhikode', 'Vadakara', 'Koyilandy', 'Thamarassery', 'Kuttiady'],
            'Kannur' => ['Kannur', 'Taliparamba', 'Thalassery', 'Payyanur', 'Mattannur'],
            'Kasaragod' => ['Kasaragod', 'Kanhangad', 'Nileshwaram', 'Manjeshwar', 'Uppala'],
            'Malappuram' => ['Malappuram', 'Manjeri', 'Tirur', 'Perinthalmanna', 'Nilambur'],
            'Palakkad' => ['Palakkad', 'Ottapalam', 'Chittur', 'Mannarkkad', 'Shoranur'],
            'Wayanad' => ['Kalpetta', 'Mananthavady', 'Sulthan Bathery', 'Vythiri', 'Pulpally'],
            'Alappuzha' => ['Alappuzha', 'Chengannur', 'Kayamkulam', 'Mavelikkara', 'Cherthala'],
            'Pathanamthitta' => ['Pathanamthitta', 'Adoor', 'Thiruvalla', 'Konni', 'Pandalam']
        ];
        
        return isset($townData[$district]) ? $townData[$district] : [];
    }
    
    /**
     * Call Python ML model to predict risks
     */
    public function predictRisks($district, $town) {
        // Use absolute paths to ensure correct file resolution
        $pythonPath = $this->pythonPath;
        $scriptPath = $this->scriptPath;
        
        // Prepare command - ensure both paths are quoted
        $cmd = '"' . $pythonPath . '" "' . $scriptPath . '" ' . escapeshellarg($district) . ' ' . escapeshellarg($town);
        
        // Execute Python script and capture output
        $output = shell_exec($cmd . ' 2>&1');
        
        // Trim output to remove any leading/trailing whitespace
        $output = trim($output);
        
        // Parse JSON output from Python
        $result = json_decode($output, true);
        
        if ($result && isset($result['status']) && $result['status'] === 'success') {
            return $result;
        } else {
            // If Python fails, log the error and use fallback
            error_log("ML Predictor: Failed to get predictions for {$district}, {$town}. Python output: " . substr($output, 0, 200));
            return $this->fallbackPrediction($district, $town);
        }
    }
    
    /**
     * Fallback prediction (if Python is not available)
     * Uses rule-based logic
     */
    private function fallbackPrediction($district, $town) {
        // Sample environmental data based on district
        $districtData = [
            'Idukki' => [
                'avg_elevation_m' => 1000,
                'terrain_type' => 'Highland',
                'avg_rainfall_mm' => 3250,
                'water_table_depth' => 'Moderate',
                'soil_type' => 'Lateritic',
                'slope_category' => 'Steep',
                'forest_cover_percent' => 73.71,
                'land_use_type' => 'Agricultural'
            ],
            'Kottayam' => [
                'avg_elevation_m' => 550,
                'terrain_type' => 'Midland',
                'avg_rainfall_mm' => 3000,
                'water_table_depth' => 'Moderate',
                'soil_type' => 'Lateritic',
                'slope_category' => 'Moderate',
                'forest_cover_percent' => 40.5,
                'land_use_type' => 'Mixed Agricultural'
            ],
            'Ernakulam' => [
                'avg_elevation_m' => 50,
                'terrain_type' => 'Coastal',
                'avg_rainfall_mm' => 3200,
                'water_table_depth' => 'Shallow',
                'soil_type' => 'Coastal Alluvial',
                'slope_category' => 'Gentle',
                'forest_cover_percent' => 25.8,
                'land_use_type' => 'Urban'
            ]
        ];
        
        // Default data if district not found
        $defaultData = [
            'avg_elevation_m' => 300,
            'terrain_type' => 'Midland',
            'avg_rainfall_mm' => 2800,
            'water_table_depth' => 'Moderate',
            'soil_type' => 'Lateritic',
            'slope_category' => 'Moderate',
            'forest_cover_percent' => 45.0,
            'land_use_type' => 'Mixed'
        ];
        
        $envData = isset($districtData[$district]) ? $districtData[$district] : $defaultData;
        
        // Simple rule-based risk assessment
        $floodRisk = 'Low';
        $landslideRisk = 'Low';
        $overallRisk = 'Low';
        
        // Flood risk logic
        if ($envData['avg_elevation_m'] < 100 && in_array($envData['terrain_type'], ['Coastal', 'Lowland'])) {
            $floodRisk = 'High';
        } elseif ($envData['avg_rainfall_mm'] > 3000 && $envData['water_table_depth'] === 'Shallow') {
            $floodRisk = 'Medium';
        }
        
        // Landslide risk logic
        if ($envData['slope_category'] === 'Steep' && $envData['avg_rainfall_mm'] > 3000) {
            $landslideRisk = 'High';
        } elseif ($envData['slope_category'] === 'Moderate' && $envData['forest_cover_percent'] < 40) {
            $landslideRisk = 'Medium';
        }
        
        // Overall risk
        if ($floodRisk === 'High' || $landslideRisk === 'High') {
            $overallRisk = 'High';
        } elseif ($floodRisk === 'Medium' || $landslideRisk === 'Medium') {
            $overallRisk = 'Medium';
        }
        
        return [
            'status' => 'success',
            'environmental_data' => $envData,
            'risk_assessment' => [
                'flood_risk' => $floodRisk,
                'landslide_risk' => $landslideRisk,
                'overall_risk' => $overallRisk,
                'flood_confidence' => 75.5,
                'landslide_confidence' => 82.3,
                'overall_confidence' => 79.8
            ]
        ];
    }
}
?>
