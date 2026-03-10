# ==========================================================
# CLEAN PREDICTION-ONLY CLI (loads pre-trained models)
# ==========================================================
# ✅ Loads pre-trained models from .pkl files
# ✅ NO training, NO diagnostics, NO plots
# ✅ Outputs ONLY clean JSON
# ==========================================================

import pandas as pd
import numpy as np
import joblib
import json
import sys
import os

# Get the directory where this script is located
script_dir = os.path.dirname(os.path.abspath(__file__))

# Load pre-trained models
try:
    overall_model = joblib.load(os.path.join(script_dir, "overall_risk_model.pkl"))
    flood_model = joblib.load(os.path.join(script_dir, "flood_risk_model.pkl"))
    landslide_model = joblib.load(os.path.join(script_dir, "landslide_risk_model.pkl"))
    scaler = joblib.load(os.path.join(script_dir, "scaler.pkl"))
    feature_encoders = joblib.load(os.path.join(script_dir, "feature_encoders.pkl"))
    target_encoders = joblib.load(os.path.join(script_dir, "target_encoders.pkl"))
except Exception as e:
    print(json.dumps({'status': 'error', 'message': f'Failed to load models: {str(e)}'}))
    sys.exit(1)

# Load dataset for typical value lookup
try:
    df_original = pd.read_excel(os.path.join(script_dir, "construction_risk_dataset_fixed.xlsx"))
except Exception as e:
    print(json.dumps({'status': 'error', 'message': f'Failed to load dataset: {str(e)}'}))
    sys.exit(1)

# Get feature names from scaler
feature_names = [
    'District', 'Town', 'Avg Elevation (m)', 'Terrain Type',
    'Avg Rainfall (mm/yr)', 'Water Table Depth (m, April)',
    'Soil Type', 'Slope Category', 'Forest Cover %', 'Land Use Type'
]

def predict_from_location(district, town):
    """
    Predict risk for a location using pre-trained models.
    Returns environmental_data and risk_assessment.
    """
    # Normalize inputs
    district = district.strip().title()
    town = town.strip().title()
    
    # Get all rows for this location from training data
    location_data = df_original[
        (df_original['District'].str.strip().str.title() == district) & 
        (df_original['Town'].str.strip().str.title() == town)
    ]
    
    if location_data.empty:
        return None
    
    # Calculate typical values from training data
    typical_values = {
        'District': district.lower(),
        'Town': town.lower(),
        'Avg Elevation (m)': int(location_data['Avg Elevation (m)'].mean()),
        'Terrain Type': location_data['Terrain Type'].mode()[0].lower(),
        'Avg Rainfall (mm/yr)': int(location_data['Avg Rainfall (mm/yr)'].mean()),
        'Water Table Depth (m, April)': location_data['Water Table Depth (m, April)'].mode()[0].lower(),
        'Soil Type': location_data['Soil Type'].mode()[0].lower(),
        'Slope Category': location_data['Slope Category'].mode()[0].lower(),
        'Forest Cover %': int(location_data['Forest Cover %'].mean()),
        'Land Use Type': location_data['Land Use Type'].mode()[0].lower()
    }
    
    # Prepare input DataFrame
    input_df = pd.DataFrame([typical_values])
    
    # Encode features
    for col in input_df.columns:
        if col in feature_encoders:
            le = feature_encoders[col]
            try:
                input_df.loc[0, col] = le.transform([input_df.loc[0, col]])[0]
            except ValueError:
                input_df.loc[0, col] = 0
    
    # Ensure column order matches training
    input_df = input_df[feature_names]
    
    # Scale
    input_scaled = scaler.transform(input_df)
    
    # Predict with all three models
    overall_pred = overall_model.predict(input_scaled)[0]
    flood_pred = flood_model.predict(input_scaled)[0]
    land_pred = landslide_model.predict(input_scaled)[0]
    
    # Get probabilities
    overall_proba = overall_model.predict_proba(input_scaled)[0]
    flood_proba = flood_model.predict_proba(input_scaled)[0]
    land_proba = landslide_model.predict_proba(input_scaled)[0]
    
    # Decode predictions
    flood_risk = target_encoders["Flood Risk"].inverse_transform([flood_pred])[0]
    landslide_risk = target_encoders["Landslide Risk"].inverse_transform([land_pred])[0]
    overall_risk = target_encoders["Overall Risk"].inverse_transform([overall_pred])[0]
    
    # Build environmental_data from typical_values
    environmental_data = {
        'avg_elevation_m': typical_values['Avg Elevation (m)'],
        'terrain_type': typical_values['Terrain Type'],
        'avg_rainfall_mm': typical_values['Avg Rainfall (mm/yr)'],
        'water_table_depth': typical_values['Water Table Depth (m, April)'],
        'soil_type': typical_values['Soil Type'],
        'slope_category': typical_values['Slope Category'],
        'forest_cover_percent': typical_values['Forest Cover %'],
        'land_use_type': typical_values['Land Use Type']
    }
    
    # Build response
    return {
        'environmental_data': environmental_data,
        'risk_assessment': {
            'flood_risk': flood_risk,
            'landslide_risk': landslide_risk,
            'overall_risk': overall_risk,
            'flood_confidence': float(max(flood_proba)*100),
            'landslide_confidence': float(max(land_proba)*100),
            'overall_confidence': float(max(overall_proba)*100)
        }
    }


# ==========================================================
# CLI MODE - Outputs ONLY JSON, NO diagnostics
# ==========================================================
if __name__ == '__main__':
    if len(sys.argv) >= 3:
        district = sys.argv[1]
        town = sys.argv[2]
        
        result = predict_from_location(district, town)
        
        if result is None:
            output = {'status': 'error', 'message': f'Location not found: {district}, {town}'}
        else:
            output = {
                'status': 'success',
                'environmental_data': result['environmental_data'],
                'risk_assessment': result['risk_assessment']
            }
        
        # Output ONLY JSON - no extra text
        print(json.dumps(output))
    else:
        output = {'status': 'error', 'message': 'Usage: python predict_cli.py <district> <town>'}
        print(json.dumps(output))
