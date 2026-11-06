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
        'message' => 'Gagal terhubung ke database MySQL. ' . (function_exists('getDbLastError') ? getDbLastError() : '')
    ]);
    exit;
}

// ================= 1. MENERIMA DATA DARI ESP32 (HTTP POST) =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_GET['action']) || $_GET['action'] === 'push')) {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    // Multi-source API Key authentication (Mencegah error 401 akibat header di-filter Nginx)
    $clientKey = '';

    // 1. Dari $_SERVER (Nginx / FastCGI environment)
    if (!empty($_SERVER['HTTP_X_API_KEY'])) {
        $clientKey = trim($_SERVER['HTTP_X_API_KEY']);
    } elseif (!empty($_SERVER['X_API_KEY'])) {
        $clientKey = trim($_SERVER['X_API_KEY']);
    } elseif (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        $clientKey = trim(str_ireplace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']));
    }

    // 2. Dari getallheaders() (Case-Insensitive)
    if (empty($clientKey) && function_exists('getallheaders')) {
        $headers = getallheaders();
        if (is_array($headers)) {
            foreach ($headers as $k => $v) {
                if (strcasecmp($k, 'X-API-KEY') === 0 || strcasecmp($k, 'X_API_KEY') === 0) {
                    $clientKey = trim($v);
                    break;
                }
            }
        }
    }

    // 3. Dari URL Query String (?api_key=...)
    if (empty($clientKey) && !empty($_GET['api_key'])) {
        $clientKey = trim($_GET['api_key']);
    }

    // 4. Dari JSON Body Payload ({ "api_key": "..." })
    if (empty($clientKey) && is_array($data) && !empty($data['api_key'])) {
        $clientKey = trim($data['api_key']);
    }
    
    // Validasi API Key (Mendukung shawir1312 dan fallback powmr_secret_key_123)
    $validKeys = [API_SECRET_KEY, 'shawir1312', 'powmr_secret_key_123'];
    if (!in_array($clientKey, $validKeys, true)) {
        http_response_code(401);
        echo json_encode([
            'status' => 'error', 
            'message' => 'Unauthorized: Invalid API Key'
        ]);
        exit;
    }

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

    $pvPower       = floatval($data['pv_power'] ?? 0);
    $pvVoltage     = floatval($data['pv_voltage'] ?? 0);
    $pvCurrent     = floatval($data['pv_current'] ?? 0);
    $batteryVolt   = floatval($data['battery_voltage'] ?? 0);
    $batterySoc    = intval($data['battery_capacity'] ?? 0);
    $chgCurrent    = floatval($data['charging_current'] ?? 0);
    $disCurrent    = floatval($data['discharge_current'] ?? 0);
    $busVolt       = floatval($data['bus_voltage'] ?? 0);
    $outputLoadW   = floatval($data['output_active_power'] ?? 0);
    $outputLoadVA  = floatval($data['output_apparent_power'] ?? 0);
    $loadPct       = intval($data['output_load_percent'] ?? 0);
    $acOutputVolt  = floatval($data['ac_output_voltage'] ?? 0);
    $acOutputFreq  = floatval($data['ac_output_freq'] ?? 0);
    $acInputVolt   = floatval($data['ac_input_voltage'] ?? 0);
    $mainsFreq     = floatval($data['mains_freq'] ?? 0);
    $mainsPower    = floatval($data['mains_power'] ?? 0);
    $opMode        = strval($data['operating_mode'] ?? 'Normal');

    // 1. Fallback jika output_active_power terbaca 0 tetapi VA atau persentase ada
    if ($outputLoadW <= 0 && $outputLoadVA > 0) {
        $outputLoadW = round($outputLoadVA * 0.95);
    } elseif ($outputLoadW <= 0 && $loadPct > 0) {
        $outputLoadW = round(($loadPct * 6500) / 100);
    }

    // 2. Koreksi Daya PLN (mains_power):
    // Ketika terhubung ke PLN (AC Input > 150V atau mode Line/Bypass):
    // Total daya PLN yang ditarik = Beban Rumah + Daya Pengisian Baterai (+10% efisiensi AC->DC)
    if ($acInputVolt > 150 || stripos($opMode, 'line') !== false || stripos($opMode, 'bypass') !== false) {
        $battChargeWatts = ($chgCurrent > 0.5) ? ($batteryVolt * $chgCurrent) : 0.0;
        $gridForBatt = max(0.0, $battChargeWatts - $pvPower) * 1.10;
        $expectedGridPower = round($outputLoadW + $gridForBatt);

        if ($mainsPower <= 0 || $mainsPower < $expectedGridPower) {
            $mainsPower = $expectedGridPower;
        }
    }

    $stmt->execute([
        ':operating_mode'       => $opMode,
        ':status_code'          => $data['status_code'] ?? '00',
        ':pv_power'             => $pvPower,
        ':pv_voltage'           => $pvVoltage,
        ':pv_current'           => $pvCurrent,
        ':battery_voltage'      => $batteryVolt,
        ':battery_capacity'     => $batterySoc,
        ':charging_current'     => $chgCurrent,
        ':discharge_current'    => $disCurrent,
        ':bus_voltage'          => $busVolt,
        ':output_active_power'  => $outputLoadW,
        ':output_apparent_power'=> $outputLoadVA,
        ':output_load_percent'  => $loadPct,
        ':ac_output_voltage'    => $acOutputVolt,
        ':ac_output_freq'       => $acOutputFreq,
        ':ac_input_voltage'     => $acInputVolt,
        ':mains_freq'           => $mainsFreq,
        ':mains_power'          => $mainsPower,
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
        $createdTs = strtotime($latest['created_at']);
        $nowTs = time();
        $diffSec = max(0, $nowTs - $createdTs);
        echo json_encode([
            'status' => 'success',
            'server_time' => date('Y-m-d H:i:s'),
            'diff_seconds' => $diffSec,
            'is_online' => ($diffSec <= 25),
            'data' => $latest
        ]);
    } else {
        echo json_encode([
            'status' => 'empty',
            'is_online' => false,
            'diff_seconds' => null,
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

