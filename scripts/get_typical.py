import pandas as pd, json, sys
path = r"C:/xampp/htdocs/mini project/construction_risk_dataset_fixed.xlsx"
if len(sys.argv) < 3:
    print(json.dumps({'status':'error','message':'Usage: get_typical.py <District> <Town>'}))
    sys.exit(1)
district=sys.argv[1].strip().title()
town=sys.argv[2].strip().title()
df=pd.read_excel(path)
loc = df[(df['District'].astype(str).str.strip().str.title()==district) & (df['Town'].astype(str).str.strip().str.title()==town)]
if loc.empty:
    print(json.dumps({'status':'error','message':'Location not found'}))
else:
    typical = {
        'avg_elevation_m': int(loc['Avg Elevation (m)'].mean()),
        'terrain_type': str(loc['Terrain Type'].mode()[0]),
        'avg_rainfall_mm': int(loc['Avg Rainfall (mm/yr)'].mean()),
        'water_table_depth': str(loc['Water Table Depth (m, April)'].mode()[0]),
        'soil_type': str(loc['Soil Type'].mode()[0]),
        'slope_category': str(loc['Slope Category'].mode()[0]),
        'forest_cover_percent': float(loc['Forest Cover %'].iloc[0]) if 'Forest Cover %' in loc.columns else None,
        'land_use_type': str(loc['Land Use Type'].mode()[0])
    }
    print(json.dumps({'status':'success','typical':typical}, indent=2))