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

    $insertedId = $pdo->lastInsertId();

    // 1. Simpan pembacaan QPIRI dari ESP32 jika dikirim
    if (!empty($data['settings']) && is_array($data['settings'])) {
        try {
            $s = $data['settings'];
            $stSet = $pdo->prepare("
                UPDATE inverter_settings SET
                    output_source_priority = COALESCE(:pop, output_source_priority),
                    charger_source_priority = COALESCE(:pcp, charger_source_priority),
                    max_ac_charge_current = COALESCE(:muchgc, max_ac_charge_current),
                    max_total_charge_current = COALESCE(:mnchgc, max_total_charge_current),
                    battery_type = COALESCE(:btype, battery_type),
                    back_to_grid_voltage = COALESCE(:pbc, back_to_grid_voltage),
                    back_to_discharge_voltage = COALESCE(:pbd, back_to_discharge_voltage),
                    cutoff_voltage = COALESCE(:pcu, cutoff_voltage),
                    bulk_voltage = COALESCE(:pbu, bulk_voltage),
                    float_voltage = COALESCE(:pfl, float_voltage),
                    raw_qpiri = COALESCE(:raw, raw_qpiri),
                    updated_at = NOW()
                WHERE id = 1
            ");
            $stSet->execute([
                ':pop'    => $s['output_source_priority'] ?? null,
                ':pcp'    => $s['charger_source_priority'] ?? null,
                ':muchgc' => isset($s['max_ac_charge_current']) ? intval($s['max_ac_charge_current']) : null,
                ':mnchgc' => isset($s['max_total_charge_current']) ? intval($s['max_total_charge_current']) : null,
                ':btype'  => $s['battery_type'] ?? null,
                ':pbc'    => isset($s['back_to_grid_voltage']) ? floatval($s['back_to_grid_voltage']) : null,
                ':pbd'    => isset($s['back_to_discharge_voltage']) ? floatval($s['back_to_discharge_voltage']) : null,
                ':pcu'    => isset($s['cutoff_voltage']) ? floatval($s['cutoff_voltage']) : null,
                ':pbu'    => isset($s['bulk_voltage']) ? floatval($s['bulk_voltage']) : null,
                ':pfl'    => isset($s['float_voltage']) ? floatval($s['float_voltage']) : null,
                ':raw'    => $s['raw_qpiri'] ?? null,
            ]);
        } catch (Exception $e) {}
    }

    // 2. Proses konfirmasi eksekusi (ACK/NAK) dari ESP32 jika dikirim via payload telemetri
    if (!empty($data['cmd_ack']) && is_array($data['cmd_ack'])) {
        try {
            $ackId = intval($data['cmd_ack']['id'] ?? 0);
            $ackRes = trim($data['cmd_ack']['result'] ?? '');
            if ($ackId > 0) {
                $newSt = (stripos($ackRes, 'ACK') !== false) ? 'success' : 'failed';
                $pdo->prepare("UPDATE inverter_commands SET status = ?, response = ?, executed_at = NOW() WHERE id = ?")
                    ->execute([$newSt, $ackRes, $ackId]);
            }
        } catch (Exception $e) {}
    }

    // 3. Bersihkan perintah 'sent' yang kedaluwarsa (lebih dari 45 detik tanpa kabar) jadi 'failed'
    try {
        $pdo->query("UPDATE inverter_commands SET status = 'failed', response = 'Timeout (Tanpa ACK)', executed_at = NOW() WHERE status = 'sent' AND created_at < NOW() - INTERVAL 45 SECOND");
    } catch (Exception $e) {}

    // 4. Periksa apakah ada antrean perintah pending untuk dikirim ke ESP32
    $pendingCmd = null;
    try {
        $cmdStmt = $pdo->query("SELECT * FROM inverter_commands WHERE status = 'pending' ORDER BY id ASC LIMIT 1");
        $pendingCmd = $cmdStmt->fetch();
        if ($pendingCmd) {
            $pdo->prepare("UPDATE inverter_commands SET status = 'sent' WHERE id = ?")->execute([$pendingCmd['id']]);
        }
    } catch (Exception $e) {}

    $respData = [
        'status' => 'success',
        'message' => 'Telemetry logged successfully',
        'id' => $insertedId
    ];

    if ($pendingCmd) {
        $respData['pending_command'] = [
            'id' => intval($pendingCmd['id']),
            'cmd' => $pendingCmd['command_code'],
            'label' => $pendingCmd['command_label']
        ];
    }

    echo json_encode($respData);
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

// ================= 3. ENDPOINT PENGATURAN PARAMETER INVERTER =================

// A. Ambil Data Pengaturan Terkini & Riwayat Perintah
if ($action === 'get_settings') {
    try {
        $st = $pdo->query("SELECT * FROM inverter_settings WHERE id = 1 LIMIT 1");
        $settings = $st->fetch();

        $cmdSt = $pdo->query("SELECT * FROM inverter_commands ORDER BY id DESC LIMIT 15");
        $commands = $cmdSt->fetchAll();

        echo json_encode([
            'status' => 'success',
            'settings' => $settings ?: [],
            'commands' => $commands ?: []
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// B. Simpan & Antrekan Perintah Perubahan Pengaturan
if ($action === 'save_settings' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    if (!$input) {
        $input = $_POST;
    }

    $commandsToQueue = [];

    // 1. Prioritas Output (POP)
    if (!empty($input['output_source_priority'])) {
        $val = strtoupper($input['output_source_priority']);
        $codeMap = ['SUB' => 'POP00', 'SBU' => 'POP01', 'UTI' => 'POP02', 'USB' => 'POP02'];
        if (isset($codeMap[$val])) {
            $commandsToQueue[] = [
                'code' => $codeMap[$val],
                'label' => "Prioritas Output: $val",
                'field' => 'output_source_priority',
                'val' => $val
            ];
        }
    }

    // 2. Prioritas Charger (PCP)
    if (!empty($input['charger_source_priority'])) {
        $val = strtoupper($input['charger_source_priority']);
        $codeMap = ['CSO' => 'PCP00', 'SNU' => 'PCP01', 'OSO' => 'PCP02', 'SOR' => 'PCP03'];
        if (isset($codeMap[$val])) {
            $commandsToQueue[] = [
                'code' => $codeMap[$val],
                'label' => "Prioritas Cas: $val",
                'field' => 'charger_source_priority',
                'val' => $val
            ];
        }
    }

    // 3. Arus Cas PLN Maksimal (MUCHGC)
    if (isset($input['max_ac_charge_current']) && is_numeric($input['max_ac_charge_current'])) {
        $amps = intval($input['max_ac_charge_current']);
        if ($amps >= 10 && $amps <= 120) {
            $commandsToQueue[] = [
                'code' => sprintf("MUCHGC%03d", $amps),
                'label' => "Arus Cas PLN: {$amps}A",
                'field' => 'max_ac_charge_current',
                'val' => $amps
            ];
        }
    }

    // 4. Arus Cas Total Maksimal (MNCHGC)
    if (isset($input['max_total_charge_current']) && is_numeric($input['max_total_charge_current'])) {
        $amps = intval($input['max_total_charge_current']);
        if ($amps >= 10 && $amps <= 150) {
            $commandsToQueue[] = [
                'code' => sprintf("MNCHGC%03d", $amps),
                'label' => "Arus Cas Total: {$amps}A",
                'field' => 'max_total_charge_current',
                'val' => $amps
            ];
        }
    }

    // 5. Back to Grid Voltage (PBCV)
    if (isset($input['back_to_grid_voltage']) && is_numeric($input['back_to_grid_voltage'])) {
        $v = floatval($input['back_to_grid_voltage']);
        $commandsToQueue[] = [
            'code' => sprintf("PBCV%04.1f", $v),
            'label' => "Tegangan Kembali PLN: {$v}V",
            'field' => 'back_to_grid_voltage',
            'val' => $v
        ];
    }

    // 6. Back to Discharge Voltage (PBDV)
    if (isset($input['back_to_discharge_voltage']) && is_numeric($input['back_to_discharge_voltage'])) {
        $v = floatval($input['back_to_discharge_voltage']);
        $commandsToQueue[] = [
            'code' => sprintf("PBDV%04.1f", $v),
            'label' => "Tegangan Baterai Aktif: {$v}V",
            'field' => 'back_to_discharge_voltage',
            'val' => $v
        ];
    }

    // 7. Cut-Off Voltage (PSDV)
    if (isset($input['cutoff_voltage']) && is_numeric($input['cutoff_voltage'])) {
        $v = floatval($input['cutoff_voltage']);
        $commandsToQueue[] = [
            'code' => sprintf("PSDV%04.1f", $v),
            'label' => "Tegangan Cut-Off Baterai: {$v}V",
            'field' => 'cutoff_voltage',
            'val' => $v
        ];
    }

    // 8. Bulk Charging Voltage (PCVV)
    if (isset($input['bulk_voltage']) && is_numeric($input['bulk_voltage'])) {
        $v = floatval($input['bulk_voltage']);
        $commandsToQueue[] = [
            'code' => sprintf("PCVV%04.1f", $v),
            'label' => "Tegangan Bulk Charge: {$v}V",
            'field' => 'bulk_voltage',
            'val' => $v
        ];
    }

    // 9. Float Charging Voltage (PBFT)
    if (isset($input['float_voltage']) && is_numeric($input['float_voltage'])) {
        $v = floatval($input['float_voltage']);
        $commandsToQueue[] = [
            'code' => sprintf("PBFT%04.1f", $v),
            'label' => "Tegangan Float Charge: {$v}V",
            'field' => 'float_voltage',
            'val' => $v
        ];
    }

    // 10. Saklar Buzzer (PEa / PDa)
    if (isset($input['buzzer'])) {
        $bz = intval($input['buzzer']) === 1;
        $commandsToQueue[] = [
            'code' => $bz ? 'PEa' : 'PDa',
            'label' => "Buzzer Alarm: " . ($bz ? 'ON' : 'OFF'),
            'field' => 'buzzer',
            'val' => $bz ? 1 : 0
        ];
    }

    // 11. Saklar Backlight (PEx / PDx)
    if (isset($input['backlight'])) {
        $bl = intval($input['backlight']) === 1;
        $commandsToQueue[] = [
            'code' => $bl ? 'PEx' : 'PDx',
            'label' => "Backlight LCD: " . ($bl ? 'ON' : 'OFF'),
            'field' => 'backlight',
            'val' => $bl ? 1 : 0
        ];
    }

    if (empty($commandsToQueue)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Tidak ada parameter pengaturan yang diubah']);
        exit;
    }

    // Masukkan ke antrean inverter_commands
    $insCmd = $pdo->prepare("INSERT INTO inverter_commands (command_code, command_label, parameter_value, status) VALUES (?, ?, ?, 'pending')");
    $queuedCount = 0;

    foreach ($commandsToQueue as $cmd) {
        $insCmd->execute([$cmd['code'], $cmd['label'], strval($cmd['val'])]);
        $queuedCount++;

        // Update database setting optimis
        if (!empty($cmd['field'])) {
            try {
                $f = $cmd['field'];
                $pdo->prepare("UPDATE inverter_settings SET `$f` = ? WHERE id = 1")->execute([$cmd['val']]);
            } catch (Exception $e) {}
        }
    }

    echo json_encode([
        'status' => 'success',
        'message' => "$queuedCount perintah pengaturan berhasil diantrekan ke inverter",
        'queued_commands' => $queuedCount
    ]);
    exit;
}

// C. Konfirmasi Eksekusi Perintah dari ESP32 (ACK / NAK)
if ($action === 'ack_command') {
    $cmdId = intval($_REQUEST['id'] ?? 0);
    $result = trim($_REQUEST['result'] ?? ''); // 'ACK' atau 'NAK'

    if ($cmdId > 0) {
        $newStatus = (stripos($result, 'ACK') !== false) ? 'success' : 'failed';
        $st = $pdo->prepare("UPDATE inverter_commands SET status = ?, response = ?, executed_at = NOW() WHERE id = ?");
        $st->execute([$newStatus, $result, $cmdId]);

        echo json_encode(['status' => 'success', 'updated_id' => $cmdId, 'new_status' => $newStatus]);
        exit;
    }
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid command ID']);
    exit;
}

// D. Bersihkan / Batalkan Semua Antrean Perintah Inverter
if ($action === 'clear_commands') {
    try {
        $pdo->query("UPDATE inverter_commands SET status = 'failed', response = 'Dibatalkan Pengguna', executed_at = NOW() WHERE status IN ('pending', 'sent')");
        echo json_encode(['status' => 'success', 'message' => 'Semua antrean perintah berhasil dibersihkan']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

http_response_code(404);
echo json_encode([
    'status' => 'error',
    'message' => 'Unknown API action'
]);

