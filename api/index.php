<?php
/**
 * CEI326 – Registry of Assets REST API
 *
 * Endpoints:
 * GET /api/?endpoint=politicians – List all politicians
 * GET /api/?endpoint=politicians&id=N – Single politician with declarations
 * GET /api/?endpoint=declarations – All submitted declarations
 * GET /api/?endpoint=declarations&year=Y – Declarations for a specific year
 * GET /api/?endpoint=declarations&party=N – Declarations for a party
 * GET /api/?endpoint=parties – List parties
 * GET /api/?endpoint=statistics – Aggregated statistics
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('X-Content-Type-Options: nosniff');

require_once '../includes/db.php';

function respond(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

function error(string $msg, int $code = 400): void {
    respond(['error' => $msg, 'code' => $code], $code);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    error('Only GET requests are supported.', 405);
}

$endpoint = trim($_GET['endpoint'] ?? '');

switch ($endpoint) {

    // ── Politicians ──────────────────────────────────────────────────────────
    case 'politicians':
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            // first_name and last_name now from users table
            $stmt = $pdo->prepare('SELECT p.id, u.first_name, u.last_name, p.children,
                pa.name AS party, pa.short_name AS party_short,
                po.title AS position, d.name AS district, u.email
            FROM politicians p
            JOIN users u ON u.id=p.user_id
            LEFT JOIN parties pa ON pa.id=p.party_id
            LEFT JOIN positions po ON po.id=p.position_id
            LEFT JOIN districts d ON d.id=p.district_id
            WHERE p.id=:id');
            $stmt->execute([':id'=>$id]);
            $pol = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$pol) error('Politician not found.', 404);

            $dStmt = $pdo->prepare("SELECT year, status, total_deposits, total_debts, annual_income, vehicles_count, vehicles_details, shares_value, shares_details, real_estate_count, real_estate_details, notes, submitted_at FROM asset_declarations WHERE politician_id=:pid AND status='submitted' ORDER BY year DESC");
            $dStmt->execute([':pid'=>$pol['id']]);
            $pol['declarations'] = $dStmt->fetchAll(PDO::FETCH_ASSOC);
            respond(['success' => true, 'data' => $pol]);
        }

        $keyword = trim($_GET['keyword'] ?? '');
        $partyId = (int)($_GET['party'] ?? 0);
        $posId = (int)($_GET['position'] ?? 0);
        $sql = 'SELECT p.id, u.first_name, u.last_name, p.children,
            pa.name AS party, pa.short_name AS party_short,
            po.title AS position, d.name AS district
        FROM politicians p
        JOIN users u ON u.id=p.user_id
        LEFT JOIN parties pa ON pa.id=p.party_id
        LEFT JOIN positions po ON po.id=p.position_id
        LEFT JOIN districts d ON d.id=p.district_id
        WHERE 1=1';
        $params = [];
        if ($keyword) { $sql .= ' AND (u.first_name LIKE :kw OR u.last_name LIKE :kw OR CONCAT(u.first_name," ",u.last_name) LIKE :kw)'; $params[':kw']='%'.$keyword.'%'; }
        if ($partyId) { $sql .= ' AND p.party_id=:pid'; $params[':pid']=$partyId; }
        if ($posId) { $sql .= ' AND p.position_id=:poid'; $params[':poid']=$posId; }
        $sql .= ' ORDER BY u.last_name, u.first_name';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        respond(['success' => true, 'count' => $stmt->rowCount(), 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    // ── Declarations ─────────────────────────────────────────────────────────
    case 'declarations':
        $year = (int)($_GET['year'] ?? 0);
        $partyId = (int)($_GET['party'] ?? 0);
        $polId = (int)($_GET['politician'] ?? 0);
        $sql = "SELECT ad.id, ad.year, ad.status, ad.total_deposits, ad.total_debts, ad.annual_income,
            ad.vehicles_count, ad.vehicles_details, ad.shares_value, ad.shares_details,
            ad.real_estate_count, ad.real_estate_details, ad.notes, ad.submitted_at,
            u.first_name, u.last_name, pa.name AS party, pa.short_name AS party_short
        FROM asset_declarations ad
        JOIN politicians p ON p.id=ad.politician_id
        JOIN users u ON u.id=p.user_id
        LEFT JOIN parties pa ON pa.id=p.party_id
        WHERE ad.status='submitted'";
        $params = [];
        if ($year) { $sql .= ' AND ad.year=:yr'; $params[':yr']=$year; }
        if ($partyId) { $sql .= ' AND p.party_id=:pid'; $params[':pid']=$partyId; }
        if ($polId) { $sql .= ' AND ad.politician_id=:polid'; $params[':polid']=$polId; }
        $sql .= ' ORDER BY ad.year DESC, u.last_name';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        respond(['success' => true, 'count' => count($rows), 'data' => $rows]);
        break;

    // ── Parties ──────────────────────────────────────────────────────────────
    case 'parties':
        $rows = $pdo->query('SELECT id, name, short_name FROM parties ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
        respond(['success' => true, 'count' => count($rows), 'data' => $rows]);
        break;

    // ── Statistics ───────────────────────────────────────────────────────────
    case 'statistics':
        $byYear = $pdo->query("SELECT year, COUNT(*) AS submissions, SUM(total_deposits) AS total_deposits, SUM(total_debts) AS total_debts, SUM(annual_income) AS total_income FROM asset_declarations WHERE status='submitted' GROUP BY year ORDER BY year DESC")->fetchAll(PDO::FETCH_ASSOC);
        $byParty = $pdo->query("SELECT pa.name AS party, pa.short_name, COUNT(ad.id) AS submissions, SUM(ad.total_deposits) AS total_deposits, SUM(ad.total_debts) AS total_debts FROM asset_declarations ad JOIN politicians p ON p.id=ad.politician_id LEFT JOIN parties pa ON pa.id=p.party_id WHERE ad.status='submitted' GROUP BY pa.name, pa.short_name ORDER BY total_deposits DESC")->fetchAll(PDO::FETCH_ASSOC);
        $totals = $pdo->query("SELECT COUNT(*) AS total_declarations, SUM(total_deposits) AS total_deposits, SUM(total_debts) AS total_debts, SUM(annual_income) AS total_income FROM asset_declarations WHERE status='submitted'")->fetch(PDO::FETCH_ASSOC);
        respond(['success'=>true,'data'=>['totals'=>$totals,'by_year'=>$byYear,'by_party'=>$byParty]]);
        break;

    // ── Default ──────────────────────────────────────────────────────────────
    default:
        respond([
            'success' => true,
            'message' => 'CEI326 Registry of Assets – REST API',
            'endpoints' => [
                'GET /api/?endpoint=politicians' => 'List all politicians',
                'GET /api/?endpoint=politicians&id=N' => 'Get politician by ID with declarations',
                'GET /api/?endpoint=politicians&keyword=...' => 'Search politicians',
                'GET /api/?endpoint=declarations' => 'All submitted declarations',
                'GET /api/?endpoint=declarations&year=Y' => 'Declarations for a specific year',
                'GET /api/?endpoint=declarations&party=N' => 'Declarations for a party',
                'GET /api/?endpoint=parties' => 'List all parties',
                'GET /api/?endpoint=statistics' => 'Aggregated statistics',
            ]
        ]);
}
