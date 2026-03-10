# Sovereign Structures 🏛️
### AI-Powered Construction Risk Assessment System

Sovereign Structures is a professional web application designed for the Government of Kerala to streamline the construction permit process. It uses **Machine Learning** to predict geographical risks (floods and landslides) based on environmental data, ensuring safer urban development.

---

## 🚀 Key Features

### 👤 Applicant Portal
- **Dashboard**: Track application status in real-time.
- **Smart Submission**: Submit construction site details with district and town-based selection.
- **Risk Report**: Download a professional PDF-style assessment report once verified.

### 👮 Permit Officer Portal
- **Site Verification**: Review pending applications.
- **AI-Logic**: View ML-predicted risks (Flood, Landslide, Overall) automatically populated based on location.
- **Approval System**: Add remarks and approve or reject applications.

### 🔑 Admin Dashboard
- **Analytics**: View statistics on pending, verified, and rejected applications.
- **Officer Management**: Approve and verify new Permit Officer accounts.

---

## 🤖 AI & Machine Learning
The core of this project is a **Random Forest Classifier** trained on Kerala's geographical datasets.
- **Input**: District, Town, Elevation, Rainfall, Soil Type, Terrain, etc.
- **Output**: Predictive risk levels (Low, Medium, High) for Floods and Landslides.
- **Bridge**: A custom PHP-to-Python bridge (`ml_predictor.php`) enables real-time AI predictions within the web interface.

---

## 🛠️ Tech Stack
- **Frontend**: HTML5, CSS3 (Modern, Responsive UI), JavaScript.
- **Backend**: PHP (XAMPP).
- **Database**: MySQL.
- **AI Engine**: Python (Scikit-learn, Pandas, Joblib).

---

## 💻 Installation & Setup

### 1. Web Server Setup
1. Download this repository into your XAMPP `htdocs` folder.
2. Open XAMPP Control Panel and start **Apache** and **MySQL**.
3. Create a database named `construction_risk_db` in `phpMyAdmin`.
4. Import the provided SQL file (if available) or run the initialization scripts.

### 2. Configuration
1. Locate `config.php.example` and rename it to `config.php`.
2. Update the `DB_PASS` and `BASE_URL` to match your local setup.

### 3. Python Setup (For AI Features)
1. Ensure you have Python installed.
2. In the project root, create a virtual environment:
   ```bash
   python -m venv .venv
   ```
3. Install dependencies:
   ```bash
   pip install pandas scikit-learn joblib
   ```

---

## 👨‍💻 Developed For
**Academic/Project Review - [Your Year/Batch]**
*Sovereign Structures aims to provide a safer, data-driven future for construction in disaster-prone regions.*
