<?php
/**
 * REST API Endpoint:
 * - Menerima HTTP POST dari ESP32-C3
 * - Melayani data JSON untuk Dashboard & Grafik Real-time
 * Lokasi: Root Directory (http://plts.shawir.id/api.php)
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

if (!$pdo) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal terhubung ke database. Periksa izin file sqlite atau konfigurasi MySQL.'
    ]);
    exit;
}

// ================= 1. MENERIMA DATA DARI ESP32 (HTTP POST) =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_GET['action']) || $_GET['action'] === 'push')) {
    // Validasi API Key (dari Header X-API-KEY atau Query Param)
    $headers = getallheaders();
    $clientKey = $headers['X-API-KEY'] ?? $headers['x-api-key'] ?? $_GET['api_key'] ?? '';
    
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
        ':operating_mode'       => $data['operating_mode'] ?? 'Normal',
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

// ================= 2. ENDPOINT GET UNTUK DASHBOARD =================
$action = $_GET['action'] ?? 'latest';

if ($action === 'latest') {
    // Ambil data paling mutakhir (1 baris terakhir)
    $stmt = $pdo->query("SELECT * FROM solar_telemetry ORDER BY id DESC LIMIT 1");
    $latest = $stmt->fetch();

    if ($latest) {
        echo json_encode([
            'status' => 'success',
            'data' => $latest
        ]);
    } else {
        echo json_encode([
            'status' => 'empty',
            'message' => 'Belum ada data telemetri yang tersimpan'
        ]);
    }
    exit;
}

if ($action === 'history') {
    // Ambil riwayat log telemetri (default limit 50)
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    if ($limit <= 0 || $limit > 500) $limit = 50;

    $stmt = $pdo->prepare("SELECT * FROM solar_telemetry ORDER BY id DESC LIMIT :limit");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'count' => count($rows),
        'data' => $rows
    ]);
    exit;
}

if ($action === 'daily') {
    // Kurva 24 jam untuk 1 tanggal tertentu (YYYY-MM-DD)
    $date = $_GET['date'] ?? date('Y-m-d');
    
    // SQLite vs MySQL format jam
    if (DB_DRIVER === 'sqlite') {
        $sql = "
            SELECT 
                strftime('%H:00', created_at) as hour_label,
                AVG(pv_power) as avg_pv_power,
                MAX(pv_power) as max_pv_power,
                AVG(output_active_power) as avg_load_power,
                MAX(output_active_power) as max_load_power,
                AVG(battery_capacity) as avg_soc,
                MAX(daily_kwh) as peak_daily_kwh
            FROM solar_telemetry 
            WHERE date(created_at) = :date
            GROUP BY strftime('%H', created_at)
            ORDER BY hour_label ASC
        ";
    } else {
        $sql = "
            SELECT 
                DATE_FORMAT(created_at, '%H:00') as hour_label,
                AVG(pv_power) as avg_pv_power,
                MAX(pv_power) as max_pv_power,
                AVG(output_active_power) as avg_load_power,
                MAX(output_active_power) as max_load_power,
                AVG(battery_capacity) as avg_soc,
                MAX(daily_kwh) as peak_daily_kwh
            FROM solar_telemetry 
            WHERE DATE(created_at) = :date
            GROUP BY DATE_FORMAT(created_at, '%H')
            ORDER BY hour_label ASC
        ";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':date' => $date]);
    $hourlyData = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'date' => $date,
        'data' => $hourlyData
    ]);
    exit;
}

if ($action === 'monthly') {
    // Data akumulasi per hari untuk 1 bulan tertentu (YYYY-MM)
    $month = $_GET['month'] ?? date('Y-m'); // Format: 2026-11
    
    if (DB_DRIVER === 'sqlite') {
        $sql = "
            SELECT 
                strftime('%Y-%m-%d', created_at) as day_date,
                strftime('%d', created_at) as day_num,
                MAX(daily_kwh) as day_solar_kwh,
                MAX(pv_power) as max_pv_watt,
                AVG(output_active_power) as avg_load_watt
            FROM solar_telemetry 
            WHERE strftime('%Y-%m', created_at) = :month
            GROUP BY day_date
            ORDER BY day_date ASC
        ";
    } else {
        $sql = "
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m-%d') as day_date,
                DATE_FORMAT(created_at, '%d') as day_num,
                MAX(daily_kwh) as day_solar_kwh,
                MAX(pv_power) as max_pv_watt,
                AVG(output_active_power) as avg_load_watt
            FROM solar_telemetry 
            WHERE DATE_FORMAT(created_at, '%Y-%m') = :month
            GROUP BY day_date
            ORDER BY day_date ASC
        ";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':month' => $month]);
    $monthlyData = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'month' => $month,
        'data' => $monthlyData
    ]);
    exit;
}

http_response_code(404);
echo json_encode([
    'status' => 'error',
    'message' => 'Unknown API action'
]);

