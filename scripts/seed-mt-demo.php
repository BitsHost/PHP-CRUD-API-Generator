<?php
/**
 * Seed local `test` DB with multitenancy demo rows (idempotent-ish).
 *
 * Usage: php scripts/seed-mt-demo.php
 *
 * Safe for localhost only — inserts labeled "MT Demo" rows for tenants 1, 2, 3.
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

use App\Config\ConfigPaths;
use App\Config\Env;

Env::load($root . '/.env');
$configDir = ConfigPaths::resolveDir($root . '/config');
$dbConfig = require ConfigPaths::db($configDir);

$pdo = new PDO(
    sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $dbConfig['host'],
        $dbConfig['dbname'],
        $dbConfig['charset'] ?? 'utf8mb4'
    ),
    $dbConfig['user'],
    $dbConfig['pass'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "Seeding MT demo data into {$dbConfig['dbname']}@{$dbConfig['host']}...\n";

$pdo->exec("DELETE FROM reservation_extras WHERE extra_name LIKE 'MT Demo%' OR tenant_id IN (1,2,3) AND extra_name LIKE 'MT Demo%'");
// Clean previous demo by name markers (keep unrelated future data)
$pdo->exec("DELETE FROM payments WHERE notes LIKE 'MT Demo%'");
$pdo->exec("DELETE FROM reservations WHERE customer_name LIKE 'MT Demo%'");
$pdo->exec("DELETE FROM seasonal_prices WHERE name LIKE 'MT Demo%'");
$pdo->exec("DELETE FROM period_pricing WHERE name LIKE 'MT Demo%'");
$pdo->exec("DELETE FROM discounts WHERE name LIKE 'MT Demo%'");
$pdo->exec("DELETE FROM extras WHERE name LIKE 'MT Demo%'");
$pdo->exec("DELETE FROM cars WHERE description LIKE 'MT Demo%' OR license_plate LIKE 'MT-%'");
$pdo->exec("DELETE FROM locations WHERE name LIKE 'MT Demo%'");
$pdo->exec("DELETE FROM settings WHERE description LIKE 'MT Demo%' OR setting_key LIKE 'mt_demo_%'");

$tenants = [
    1 => [
        'slug' => 'bitsworld',
        'city' => 'Bucharest',
        'brand' => 'Dacia',
        'plates' => ['MT-B-1001', 'MT-B-1002'],
        'models' => [['Logan', 'economy', 35.00], ['Duster', 'suv', 55.00]],
    ],
    2 => [
        'slug' => 'testcorp',
        'city' => 'Cluj-Napoca',
        'brand' => 'Volkswagen',
        'plates' => ['MT-CJ-2001', 'MT-CJ-2002'],
        'models' => [['Golf', 'compact', 42.00], ['Tiguan', 'suv', 68.00]],
    ],
    3 => [
        'slug' => 'freeuser',
        'city' => 'Timisoara',
        'brand' => 'Toyota',
        'plates' => ['MT-TM-3001'],
        'models' => [['Corolla', 'compact', 40.00]],
    ],
];

$locIds = [];
$carIds = [];
$extraIds = [];

foreach ($tenants as $tid => $t) {
    $stmt = $pdo->prepare(
        'INSERT INTO locations (tenant_id, name, address, city, postal_code, country, phone, email, status)
         VALUES (:tid, :name, :addr, :city, :zip, :country, :phone, :email, :status)'
    );
    $stmt->execute([
        'tid' => $tid,
        'name' => "MT Demo {$t['slug']} Airport",
        'addr' => "Airport Rd {$tid}",
        'city' => $t['city'],
        'zip' => '0' . $tid . '0001',
        'country' => 'Romania',
        'phone' => '+40-700-000-00' . $tid,
        'email' => "desk@{$t['slug']}.demo",
        'status' => 'active',
    ]);
    $locIds[$tid] = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare(
        'INSERT INTO locations (tenant_id, name, address, city, country, status)
         VALUES (:tid, :name, :addr, :city, :country, :status)'
    );
    $stmt->execute([
        'tid' => $tid,
        'name' => "MT Demo {$t['slug']} Downtown",
        'addr' => "Center St {$tid}",
        'city' => $t['city'],
        'country' => 'Romania',
        'status' => 'active',
    ]);

    $carIds[$tid] = [];
    foreach ($t['models'] as $i => [$model, $cat, $price]) {
        $plate = $t['plates'][$i];
        $stmt = $pdo->prepare(
            'INSERT INTO cars
             (tenant_id, brand, model, year, license_plate, plate_number, category, transmission, fuel_type,
              seats, doors, price_per_day, description, features, status, quantity)
             VALUES
             (:tid, :brand, :model, :year, :plate, :plate2, :cat, :trans, :fuel,
              :seats, :doors, :price, :desc, :feat, :status, :qty)'
        );
        $stmt->execute([
            'tid' => $tid,
            'brand' => $t['brand'],
            'model' => $model,
            'year' => 2022 + $i,
            'plate' => $plate,
            'plate2' => $plate,
            'cat' => $cat,
            'trans' => $i === 0 ? 'manual' : 'automatic',
            'fuel' => $cat === 'suv' ? 'diesel' : 'petrol',
            'seats' => $cat === 'suv' ? 5 : 5,
            'doors' => 4,
            'price' => $price,
            'desc' => "MT Demo fleet for tenant {$tid}",
            'feat' => json_encode(['ac' => true, 'bluetooth' => true, 'tenant' => $tid]),
            'status' => 'available',
            'qty' => 1,
        ]);
        $carIds[$tid][] = (int)$pdo->lastInsertId();
    }

    $stmt = $pdo->prepare(
        'INSERT INTO extras (tenant_id, name, description, price_per_day, status, category, is_active)
         VALUES (:tid, :name, :desc, :price, :status, :cat, 1)'
    );
    $stmt->execute([
        'tid' => $tid,
        'name' => 'MT Demo GPS',
        'desc' => "Navigation unit for tenant {$tid}",
        'price' => 5.00 + $tid,
        'status' => 'active',
        'cat' => 'equipment',
    ]);
    $extraIds[$tid] = (int)$pdo->lastInsertId();

    $stmt->execute([
        'tid' => $tid,
        'name' => 'MT Demo Child Seat',
        'desc' => "Child seat for tenant {$tid}",
        'price' => 8.00,
        'status' => 'active',
        'cat' => 'equipment',
    ]);

    $stmt = $pdo->prepare(
        'INSERT INTO discounts
         (tenant_id, name, description, code, discount_type, discount_value, min_rental_days,
          valid_from, valid_until, status, is_active, auto_apply)
         VALUES
         (:tid, :name, :desc, :code, :dtype, :dval, :mindays, :from, :until, :status, 1, 0)'
    );
    $stmt->execute([
        'tid' => $tid,
        'name' => 'MT Demo Weekend',
        'desc' => "Demo discount tenant {$tid}",
        'code' => 'MTDEMO' . $tid,
        'dtype' => 'percentage',
        'dval' => 5.00 * $tid,
        'mindays' => 2,
        'from' => '2026-01-01',
        'until' => '2026-12-31',
        'status' => 'active',
    ]);

    $stmt = $pdo->prepare(
        'INSERT INTO period_pricing
         (tenant_id, name, min_days, max_days, discount_percentage, fixed_price, applies_to, status, is_active)
         VALUES (:tid, :name, :min, :max, :pct, :fixed, :applies, :status, 1)'
    );
    $stmt->execute([
        'tid' => $tid,
        'name' => 'MT Demo Weekly',
        'min' => 7,
        'max' => 13,
        'pct' => 10.00,
        'fixed' => 0.00,
        'applies' => 'all',
        'status' => 'active',
    ]);

    $stmt = $pdo->prepare(
        'INSERT INTO seasonal_prices
         (tenant_id, name, start_date, end_date, modifier_type, modifier_value, price_modifier, status, is_active)
         VALUES (:tid, :name, :start, :end, :mtype, :mval, :pmod, :status, 1)'
    );
    $stmt->execute([
        'tid' => $tid,
        'name' => 'MT Demo Summer',
        'start' => '2026-06-01',
        'end' => '2026-08-31',
        'mtype' => 'percentage',
        'mval' => 15.00,
        'pmod' => 15.00,
        'status' => 'active',
    ]);

    $stmt = $pdo->prepare(
        'INSERT INTO settings (tenant_id, setting_key, setting_value, setting_type, description)
         VALUES (:tid, :key, :val, :type, :desc)'
    );
    $stmt->execute([
        'tid' => $tid,
        'key' => 'mt_demo_currency',
        'val' => $tid === 2 ? 'EUR' : 'RON',
        'type' => 'text',
        'desc' => 'MT Demo currency setting',
    ]);
    $stmt->execute([
        'tid' => $tid,
        'key' => 'mt_demo_company',
        'val' => $t['slug'],
        'type' => 'text',
        'desc' => 'MT Demo company slug',
    ]);
}

// Reservations + extras + payments (tenants 1 and 2)
foreach ([1, 2] as $tid) {
    $carId = $carIds[$tid][0];
    $locId = $locIds[$tid];
    $userId = $tid === 1 ? 2 : 5; // john.owner / testcorp.admin

    $stmt = $pdo->prepare(
        'INSERT INTO reservations
         (tenant_id, user_id, car_id, customer_name, customer_email, customer_phone,
          pickup_date, pickup_time, return_date, return_time,
          pickup_location_id, dropoff_location_id, pickup_location, return_location,
          total_price, status, payment_method, payment_status, paid_amount, notes, language)
         VALUES
         (:tid, :uid, :car, :cname, :cemail, :cphone,
          :pdate, :ptime, :rdate, :rtime,
          :ploc, :dloc, :plabel, :rlabel,
          :total, :status, :pmethod, :pstatus, :paid, :notes, :lang)'
    );
    $stmt->execute([
        'tid' => $tid,
        'uid' => $userId,
        'car' => $carId,
        'cname' => "MT Demo Customer T{$tid}",
        'cemail' => "customer{$tid}@demo.local",
        'cphone' => '+40-711-000-00' . $tid,
        'pdate' => '2026-07-10',
        'ptime' => '10:00:00',
        'rdate' => '2026-07-13',
        'rtime' => '10:00:00',
        'ploc' => $locId,
        'dloc' => $locId,
        'plabel' => "MT Demo pickup T{$tid}",
        'rlabel' => "MT Demo return T{$tid}",
        'total' => 120.00 * $tid,
        'status' => 'confirmed',
        'pmethod' => 'card',
        'pstatus' => 'paid',
        'paid' => 120.00 * $tid,
        'notes' => "MT Demo reservation tenant {$tid}",
        'lang' => 'en',
    ]);
    $resId = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare(
        'INSERT INTO reservation_extras
         (reservation_id, extra_id, extra_name, price_per_day, days, total_price, tenant_id)
         VALUES (:rid, :eid, :ename, :ppd, :days, :total, :tid)'
    );
    $stmt->execute([
        'rid' => $resId,
        'eid' => $extraIds[$tid],
        'ename' => 'MT Demo GPS',
        'ppd' => 5.00 + $tid,
        'days' => 3,
        'total' => (5.00 + $tid) * 3,
        'tid' => $tid,
    ]);

    $stmt = $pdo->prepare(
        'INSERT INTO payments
         (tenant_id, reservation_id, method, amount, currency, status, payer_email, notes, completed_at)
         VALUES (:tid, :rid, :method, :amount, :cur, :status, :email, :notes, NOW())'
    );
    $stmt->execute([
        'tid' => $tid,
        'rid' => $resId,
        'method' => 'card',
        'amount' => 120.00 * $tid,
        'cur' => $tid === 2 ? 'EUR' : 'RON',
        'status' => 'completed',
        'email' => "customer{$tid}@demo.local",
        'notes' => "MT Demo payment tenant {$tid}",
    ]);
}

echo "\n=== Counts by tenant_id ===\n";
foreach (['locations', 'cars', 'extras', 'discounts', 'settings', 'period_pricing', 'seasonal_prices', 'reservations', 'payments'] as $table) {
    echo "\n$table:\n";
    foreach ($pdo->query("SELECT tenant_id, COUNT(*) AS c FROM `$table` GROUP BY tenant_id ORDER BY tenant_id") as $r) {
        echo "  tenant {$r['tenant_id']}: {$r['c']}\n";
    }
}

echo "\nDone. Re-run safe: deletes only MT Demo / MT-* labeled rows first.\n";
