<?php
/**
 * REST API Endpoint:
 * - Menerima HTTP POST dari ESP32-C3
 * - Melayani data JSON untuk Dashboard & Grafik
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-KEY');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

$pdo = getDbConnection();

// ================= 1. MENERIMA DATA DARI ESP32 (HTTP POST) =================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi API Key
    $headers = getallheaders();
    $clientKey = $headers['X-API-KEY'] ?? $headers['x-api-key'] ?? '';
    
    if ($clientKey !== API_SECRET_KEY) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized: Invalid API Key']);
        exit;
    }

    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO solar_telemetry (
            operating_mode, status_code,
            pv_power, pv_voltage, pv_current,
            battery_voltage, battery_capacity, charging_current, discharge_current, bus_voltage,
            output_active_power, output_apparent_power, output_load_percent,
            ac_output_voltage, ac_output_freq,
            ac_input_voltage, mains_freq, mains_power,
            inverter_temp, boost_temp, trafo_temp, pv_temp, fan1_speed, fan2_speed,
            daily_kwh, monthly_kwh, yearly_kwh, total_kwh
        ) VALUES (
            :operating_mode, :status_code,
            :pv_power, :pv_voltage, :pv_current,
            :battery_voltage, :battery_capacity, :charging_current, :discharge_current, :bus_voltage,
            :output_active_power, :output_apparent_power, :output_load_percent,
            :ac_output_voltage, :ac_output_freq,
            :ac_input_voltage, :mains_freq, :mains_power,
            :inverter_temp, :boost_temp, :trafo_temp, :pv_temp, :fan1_speed, :fan2_speed,
            :daily_kwh, :monthly_kwh, :yearly_kwh, :total_kwh
        )
    ");

    $stmt->execute([
        ':operating_mode'       => $data['operating_mode'] ?? 'Standby',
        ':status_code'          => $data['status_code'] ?? '00',
        ':pv_power'             => floatval($data['pv_power'] ?? 0),
        ':pv_voltage'           => floatval($data['pv_voltage'] ?? 0),
        ':pv_current'           => floatval($data['pv_current'] ?? 0),
        ':battery_voltage'      => floatval($data['battery_voltage'] ?? 0),
        ':battery_capacity'     => intval($data['battery_capacity'] ?? 0),
        ':charging_current'     => floatval($data['charging_current'] ?? 0),
        ':discharge_current'    => floatval($data['discharge_current'] ?? 0),
        ':bus_voltage'          => floatval($data['bus_voltage'] ?? 0),
        ':output_active_power'  => floatval($data['output_active_power'] ?? 0),
        ':output_apparent_power'=> floatval($data['output_apparent_power'] ?? 0),
        ':output_load_percent'  => intval($data['output_load_percent'] ?? 0),
        ':ac_output_voltage'    => floatval($data['ac_output_voltage'] ?? 0),
        ':ac_output_freq'       => floatval($data['ac_output_freq'] ?? 0),
        ':ac_input_voltage'     => floatval($data['ac_input_voltage'] ?? 0),
        ':mains_freq'           => floatval($data['mains_freq'] ?? 0),
        ':mains_power'          => floatval($data['mains_power'] ?? 0),
        ':inverter_temp'        => floatval($data['inverter_temp'] ?? 0),
        ':boost_temp'           => floatval($data['boost_temp'] ?? 0),
        ':trafo_temp'           => floatval($data['trafo_temp'] ?? 0),
        ':pv_temp'              => floatval($data['pv_temp'] ?? 0),
        ':fan1_speed'           => intval($data['fan1_speed'] ?? 0),
        ':fan2_speed'           => intval($data['fan2_speed'] ?? 0),
        ':daily_kwh'            => floatval($data['daily_kwh'] ?? 0),
        ':monthly_kwh'          => floatval($data['monthly_kwh'] ?? 0),
        ':yearly_kwh'           => floatval($data['yearly_kwh'] ?? 0),
        ':total_kwh'            => floatval($data['total_kwh'] ?? 0),
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Telemetry logged successfully',
        'id' => $pdo->lastInsertId()
    ]);
    exit;
}

// ================= 2. MELAYANI DATA TELEMETRI (HTTP GET) =================
$action = $_GET['action'] ?? 'latest';

// A. Telemetri Terakhir (Realtime)
if ($action === 'latest') {
    $stmt = $pdo->query("SELECT * FROM solar_telemetry ORDER BY id DESC LIMIT 1");
    $row = $stmt->fetch();
    if ($row) {
        echo json_encode(['status' => 'success', 'data' => $row]);
    } else {
        echo json_encode(['status' => 'empty', 'message' => 'No data recorded yet']);
    }
    exit;
}

// B. Laporan Harian (Grafik per Jam)
if ($action === 'daily') {
    $date = $_GET['date'] ?? date('Y-m-d');
    
    // SQLite: strftime('%H', created_at) | MySQL: HOUR(created_at)
    $hourExpr = (DB_DRIVER === 'sqlite') ? "strftime('%H', created_at)" : "DATE_FORMAT(created_at, '%H')";
    
    $query = "
        SELECT 
            {$hourExpr} as hour,
            AVG(pv_power) as avg_pv_power,
            MAX(pv_power) as peak_pv_power,
            AVG(output_active_power) as avg_load_power,
            AVG(battery_capacity) as avg_battery_soc,
            AVG(battery_voltage) as avg_battery_volt,
            MAX(daily_kwh) as max_daily_kwh
        FROM solar_telemetry 
        WHERE DATE(created_at) = :date
        GROUP BY {$hourExpr}
        ORDER BY {$hourExpr} ASC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([':date' => $date]);
    $hourly = $stmt->fetchAll();

    // Rangkuman statistik hari tersebut
    $statQuery = "
        SELECT 
            MAX(daily_kwh) as total_daily_kwh,
            MAX(pv_power) as peak_pv_power,
            AVG(output_active_power) as avg_load_power,
            AVG(battery_capacity) as avg_soc
        FROM solar_telemetry 
        WHERE DATE(created_at) = :date
    ";
    $statStmt = $pdo->prepare($statQuery);
    $statStmt->execute([':date' => $date]);
    $summary = $statStmt->fetch();

    $dailyKwh = floatval($summary['total_daily_kwh'] ?? 0);
    $savingsRp = $dailyKwh * PLN_TARIFF_PER_KWH;

    echo json_encode([
        'status' => 'success',
        'date' => $date,
        'summary' => [
            'total_daily_kwh' => $dailyKwh,
            'peak_pv_power' => floatval($summary['peak_pv_power'] ?? 0),
            'avg_load_power' => floatval($summary['avg_load_power'] ?? 0),
            'avg_soc' => floatval($summary['avg_soc'] ?? 0),
            'savings_rp' => $savingsRp
        ],
        'hourly' => $hourly
    ]);
    exit;
}

// C. Laporan Bulanan (Grafik per Tanggal)
if ($action === 'monthly') {
    $month = $_GET['month'] ?? date('Y-m'); // Format YYYY-MM
    
    $dateExpr = (DB_DRIVER === 'sqlite') ? "strftime('%Y-%m-%d', created_at)" : "DATE_FORMAT(created_at, '%Y-%m-%d')";
    $dayExpr = (DB_DRIVER === 'sqlite') ? "strftime('%d', created_at)" : "DATE_FORMAT(created_at, '%d')";

    $query = "
        SELECT 
            {$dateExpr} as full_date,
            {$dayExpr} as day,
            MAX(daily_kwh) as daily_kwh,
            MAX(pv_power) as peak_pv_power,
            AVG(output_active_power) as avg_load_power
        FROM solar_telemetry 
        WHERE {$dateExpr} LIKE :monthPattern
        GROUP BY {$dateExpr}
        ORDER BY {$dateExpr} ASC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([':monthPattern' => $month . '%']);
    $dailyList = $stmt->fetchAll();

    // Total akumulasi bulan ini
    $totalMonthKwh = 0;
    foreach ($dailyList as $row) {
        $totalMonthKwh += floatval($row['daily_kwh'] ?? 0);
    }
    $savingsRp = $totalMonthKwh * PLN_TARIFF_PER_KWH;

    echo json_encode([
        'status' => 'success',
        'month' => $month,
        'total_month_kwh' => $totalMonthKwh,
        'savings_rp' => $savingsRp,
        'days' => $dailyList
    ]);
    exit;
}

// D. Log Riwayat (History)
if ($action === 'history') {
    $limit = intval($_GET['limit'] ?? 100);
    if ($limit > 500) $limit = 500;
    
    $stmt = $pdo->prepare("SELECT * FROM solar_telemetry ORDER BY id DESC LIMIT :limit");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    echo json_encode(['status' => 'success', 'data' => $rows]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Action not recognized']);

