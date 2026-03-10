# ==========================================================
# SIMPLIFIED LOCATION-BASED CONSTRUCTION RISK PREDICTION
# ==========================================================
# ✅ Single dataset approach (more efficient)
# ✅ Clean user output (no technical details)
# ✅ User inputs: District + Town only
# ==========================================================

import pandas as pd
import numpy as np
import matplotlib.pyplot as plt
import seaborn as sns
import joblib

from sklearn.model_selection import train_test_split, StratifiedKFold, cross_validate
from sklearn.preprocessing import StandardScaler, LabelEncoder, label_binarize
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import (
    accuracy_score, precision_score, recall_score,
    confusion_matrix, classification_report,
    roc_curve, auc
)

# ==========================================================
# 1. LOAD DATASET (ONLY ONE DATASET NEEDED!)
# ==========================================================
df = pd.read_excel("construction_risk_dataset_fixed.xlsx")
print("Dataset Loaded Successfully")
print(f"Shape: {df.shape}")

# ==========================================================
# 2. DATA CLEANING
# ==========================================================
df = df.drop_duplicates()

print("\nMissing values BEFORE cleaning:")
print(df.isnull().sum())

for col in df.select_dtypes(include=np.number):
    df[col] = df[col].fillna(df[col].mean())

print("\nMissing values AFTER cleaning:")
print(df.isnull().sum())

# ==========================================================
# 3. DEFINE FEATURES & TARGETS
# ==========================================================
target_cols = ["Overall Risk", "Flood Risk", "Landslide Risk"]
X = df.drop(columns=target_cols)

y_overall = df["Overall Risk"]
y_flood = df["Flood Risk"]
y_landslide = df["Landslide Risk"]

# ==========================================================
# 4. ENCODING (FEATURES & TARGETS SEPARATELY)
# ==========================================================
feature_encoders = {}
target_encoders = {}

# Encode categorical FEATURES
for col in X.select_dtypes(include='object').columns:
    le = LabelEncoder()
    X[col] = le.fit_transform(X[col].str.lower())
    feature_encoders[col] = le

# Encode TARGETS
for target in target_cols:
    le = LabelEncoder()
    df[target] = le.fit_transform(df[target])
    target_encoders[target] = le

y_overall = df["Overall Risk"]
y_flood = df["Flood Risk"]
y_landslide = df["Landslide Risk"]

# ==========================================================
# 5. FEATURE SCALING
# ==========================================================
scaler = StandardScaler()
X_scaled = scaler.fit_transform(X)

# ==========================================================
# 6. CONSISTENT TRAIN–TEST SPLIT
# ==========================================================
(
    X_train, X_test,
    y_overall_train, y_overall_test,
    y_flood_train, y_flood_test,
    y_land_train, y_land_test
) = train_test_split(
    X_scaled,
    y_overall,
    y_flood,
    y_landslide,
    test_size=0.3,
    random_state=42,
    stratify=y_overall
)

# ==========================================================
# 7. MODEL DEFINITION
# ==========================================================
def build_model():
    return RandomForestClassifier(
        n_estimators=40,
        max_depth=4,
        min_samples_split=20,
        min_samples_leaf=10,
        max_features="sqrt",
        class_weight="balanced",
        random_state=42
    )

overall_model = build_model()
flood_model = build_model()
landslide_model = build_model()

# ==========================================================
# 8. TRAIN MODELS
# ==========================================================
print("\n" + "="*60)
print("TRAINING MODELS...")
print("="*60)

overall_model.fit(X_train, y_overall_train)
flood_model.fit(X_train, y_flood_train)
landslide_model.fit(X_train, y_land_train)

print("✅ Models trained successfully!")

# ==========================================================
# 9. EVALUATION FUNCTIONS
# ==========================================================
def evaluate_model(model, X_test, y_test, title):
    preds = model.predict(X_test)

    print(f"\n===== {title} =====")
    print("Accuracy :", accuracy_score(y_test, preds))
    print("Precision:", precision_score(y_test, preds, average="macro", zero_division=0))
    print("Recall   :", recall_score(y_test, preds, average="macro", zero_division=0))
    print("\nClassification Report:")
    print(classification_report(y_test, preds, zero_division=0))

    cm = confusion_matrix(y_test, preds)
    plt.figure(figsize=(5,4))
    sns.heatmap(cm, annot=True, fmt="d", cmap="Blues")
    plt.title(f"{title} Confusion Matrix")
    plt.xlabel("Predicted")
    plt.ylabel("Actual")
    # plt.show()

def plot_roc_curve(model, X_test, y_test, target_encoder, title):
    y_score = model.predict_proba(X_test)
    y_test_bin = label_binarize(
        y_test,
        classes=np.arange(len(target_encoder.classes_))
    )

    plt.figure(figsize=(6,5))
    for i, class_name in enumerate(target_encoder.classes_):
        fpr, tpr, _ = roc_curve(y_test_bin[:, i], y_score[:, i])
        roc_auc = auc(fpr, tpr)
        plt.plot(fpr, tpr, label=f"{class_name} (AUC = {roc_auc:.2f})")

    plt.plot([0, 1], [0, 1], "k--")
    plt.xlabel("False Positive Rate")
    plt.ylabel("True Positive Rate")
    plt.title(f"{title} ROC Curve")
    plt.legend()
    plt.grid(True)
    # plt.show()

def cross_validated_evaluation(model, X, y, title):
    cv = StratifiedKFold(n_splits=5, shuffle=True, random_state=42)
    scoring = {
        "accuracy": "accuracy",
        "precision": "precision_macro",
        "recall": "recall_macro",
        "f1": "f1_macro"
    }

    scores = cross_validate(model, X, y, cv=cv, scoring=scoring)

    print(f"\n===== {title} (Cross Validation) =====")
    print("Accuracy :", scores["test_accuracy"].mean())
    print("Precision:", scores["test_precision"].mean())
    print("Recall   :", scores["test_recall"].mean())
    print("F1 Score :", scores["test_f1"].mean())

# ==========================================================
# 10. TEST SET EVALUATION
# ==========================================================
print("\n" + "="*60)
print("MODEL EVALUATION")
print("="*60)

evaluate_model(overall_model, X_test, y_overall_test, "Overall Risk")
evaluate_model(flood_model, X_test, y_flood_test, "Flood Risk")
evaluate_model(landslide_model, X_test, y_land_test, "Landslide Risk")

# ==========================================================
# 11. ROC CURVES
# ==========================================================
plot_roc_curve(
    overall_model, X_test, y_overall_test,
    target_encoders["Overall Risk"],
    "Overall Risk"
)

plot_roc_curve(
    flood_model, X_test, y_flood_test,
    target_encoders["Flood Risk"],
    "Flood Risk"
)

plot_roc_curve(
    landslide_model, X_test, y_land_test,
    target_encoders["Landslide Risk"],
    "Landslide Risk"
)

# ==========================================================
# 12. CROSS VALIDATION
# ==========================================================
cross_validated_evaluation(overall_model, X_scaled, y_overall, "Overall Risk")
cross_validated_evaluation(flood_model, X_scaled, y_flood, "Flood Risk")
cross_validated_evaluation(landslide_model, X_scaled, y_landslide, "Landslide Risk")

# ==========================================================
# 13. FEATURE IMPORTANCE
# ==========================================================
def plot_feature_importance(model, feature_names, title):
    importances = model.feature_importances_
    indices = np.argsort(importances)

    plt.figure(figsize=(8,5))
    plt.barh(range(len(indices)), importances[indices])
    plt.yticks(range(len(indices)), np.array(feature_names)[indices])
    plt.title(title)
    plt.tight_layout()
    # plt.show()

plot_feature_importance(overall_model, X.columns, "Overall Risk Feature Importance")
plot_feature_importance(flood_model, X.columns, "Flood Risk Feature Importance")
plot_feature_importance(landslide_model, X.columns, "Landslide Risk Feature Importance")

# ==========================================================
# 14. SAVE MODELS
# ==========================================================
joblib.dump(overall_model, "overall_risk_model.pkl")
joblib.dump(flood_model, "flood_risk_model.pkl")
joblib.dump(landslide_model, "landslide_risk_model.pkl")
joblib.dump(scaler, "scaler.pkl")
joblib.dump(feature_encoders, "feature_encoders.pkl")
joblib.dump(target_encoders, "target_encoders.pkl")

print("\n✅ All models, encoders, and scaler saved successfully")

# ==========================================================
# 15. SIMPLIFIED PREDICTION FUNCTION
# ==========================================================
# Store original dataset for lookup
df_original = pd.read_excel("construction_risk_dataset_fixed.xlsx")

def predict_from_location(district, town, show_details=False):
    """
    Predict risk for a location using only District and Town.
    Automatically calculates typical values from training data.
    
    Args:
        district: District name
        town: Town name
        show_details: If True, show technical details (for debugging)
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
        print(f"\n❌ Location not found: {district}, {town}")
        print("\n📍 Available locations:")
        available = df_original.groupby('District')['Town'].unique()
        for dist in sorted(available.index)[:5]:  # Show first 5 districts
            towns = available[dist]
            print(f"  {dist}: {', '.join(sorted(towns)[:5])}...")
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
        'Forest Cover %': location_data['Forest Cover %'].iloc[0],
        'Land Use Type': location_data['Land Use Type'].mode()[0].lower()
    }
    
    # Show details only if requested (for debugging)
    if show_details:
        print(f"\n📍 Location: {district}, {town}")
        print("\nTypical Environmental Conditions:")
        print(f"  Elevation: {typical_values['Avg Elevation (m)']} m")
        print(f"  Terrain: {typical_values['Terrain Type'].title()}")
        print(f"  Rainfall: {typical_values['Avg Rainfall (mm/yr)']} mm/yr")
        print(f"  Slope: {typical_values['Slope Category'].title()}")
        print(f"  Forest Cover: {typical_values['Forest Cover %']}%")
    
    # Prepare input DataFrame
    input_df = pd.DataFrame([typical_values])
    
    # Encode features
    for col in input_df.columns:
        if col in feature_encoders:
            le = feature_encoders[col]
            try:
                input_df.loc[0, col] = le.transform([input_df.loc[0, col]])[0]
            except ValueError:
                # Use most common class as fallback
                input_df.loc[0, col] = 0
    
    # Ensure column order
    input_df = input_df[X.columns]
    
    # Scale
    input_scaled = scaler.transform(input_df)
    
    # Predict
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
    
    # Clean output for users
    print("\n" + "="*60)
    print(f"📍 RISK ASSESSMENT: {district}, {town}")
    print("="*60)
    print(f"Flood Risk:     {flood_risk}")
    print(f"Landslide Risk: {landslide_risk}")
    print(f"Overall Risk:   {overall_risk}")
    print("="*60)
    
    # Optional: Show confidence if needed
    if show_details:
        print(f"\nConfidence Scores:")
        print(f"  Flood: {max(flood_proba)*100:.1f}%")
        print(f"  Landslide: {max(land_proba)*100:.1f}%")
        print(f"  Overall: {max(overall_proba)*100:.1f}%")
    
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

    return {
        'environmental_data': environmental_data,
        'risk_assessment': {
            'flood_risk': flood_risk,
            'landslide_risk': landslide_risk,
            'overall_risk': overall_risk,
            'flood_confidence': max(flood_proba)*100,
            'landslide_confidence': max(land_proba)*100,
            'overall_confidence': max(overall_proba)*100
        }
    }


# ==========================================================
# 16. INTERACTIVE PREDICTION
# ==========================================================
if __name__ == '__main__':
    import sys, json
    # If called with district and town args, run in CLI mode and output JSON
    if len(sys.argv) >= 3:
        d = sys.argv[1]
        t = sys.argv[2]
        res = predict_from_location(d, t, show_details=False)
        if res is None:
            print(json.dumps({'status': 'error', 'message': f'Location not found: {d}, {t}'}))
            sys.exit(0)
        out = {'status': 'success', 'environmental_data': res.get('environmental_data', {}), 'risk_assessment': res.get('risk_assessment', {})}
        print(json.dumps(out))
        sys.exit(0)
    else:
        print("\n" + "="*60)
        print("ENTER LOCATION FOR PREDICTION")
        print("="*60)

        district_input = input("\nEnter District: ").strip()
        town_input = input("Enter Town: ").strip()

        if district_input and town_input:
            result = predict_from_location(district_input, town_input, show_details=False)
            if result:
                print("\n✅ Prediction completed successfully!")
        else:
            print("\n⚠️  No input provided. Skipping prediction.")

        print("\n" + "="*60)
print("PROGRAM COMPLETED")
print("="*60)
