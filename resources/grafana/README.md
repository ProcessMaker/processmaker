# Grafana Dashboards

This folder contains exported JSON files of Grafana dashboards. Each JSON file represents a configured dashboard that can be imported into Grafana to visualize metrics and data.

## 📁 Folder Contents

- **Dashboard JSON Files**: These files contain the configuration of various Grafana dashboards. Each file is a snapshot of a Grafana dashboard, including its panels, data sources, and visualizations.
- Example: `MainDashboard.json` – A JSON file for monitoring cache hit/miss rates.

---

## 🔄 How to Import a Dashboard to Grafana

Follow these steps to import any of the dashboards from this folder into your Grafana instance:

### 1. Open Grafana
- Log in to your Grafana instance.

### 2. Go to Import Dashboard
- From the left-hand menu, select **Dashboards**.
- On the top right, click **+ Plus button** > **Import Dashboard**.

### 3. Upload the JSON File
- **Option 1**: Click **Upload JSON file** and select the desired `.json` file from this folder.
- **Option 2**: Open the JSON file in a text editor, copy its content, and paste it into the **Import via panel JSON** section.

### 4. Configure Data Source
- If the dashboard relies on specific data sources (e.g., Prometheus), you may need to reassign them during the import process.
  - Example: The exported JSON might reference a data source like `prometheus-pm-spring-2025`. Ensure you have a compatible data source configured in Grafana.

### 5. Save and View the Dashboard
- Once imported, you can save and view the dashboard in your Grafana instance.

---

## ⚙️ Customization
After importing a dashboard, you can:
- Update queries to match your metrics.
- Adjust visualization settings to fit your requirements.
- Modify the layout or add/remove panels.

---

Enjoy visualizing your metrics! 🚀