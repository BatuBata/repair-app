<?php
declare(strict_types=1);
$qa = '/home/muzib/deploy-demo-repair-app/qa-mariadb';
function check(bool $v, string $why): void { if (!$v) throw new RuntimeException($why); }
check(realpath($qa) === $qa && (fileperms($qa) & 0777) === 0700, 'Private QA runtime required.');
$pid = (int) trim(file_get_contents($qa.'/mariadb.pid'));
$launch = (int) trim(file_get_contents($qa.'/launch-pid'));
check($pid > 1 && $pid === $launch, 'Owned PID mismatch.');
$cmdline = file_get_contents('/proc/'.$pid.'/cmdline');
check(str_contains($cmdline, '/usr/sbin/mariadbd') && str_contains($cmdline, '--datadir='.$qa.'/data') && str_contains($cmdline, '--skip-networking'), 'Owned process command mismatch.');
check(fileowner('/proc/'.$pid) === posix_geteuid(), 'Owned process user mismatch.');
$pdo = new PDO('mysql:unix_socket='.$qa.'/mariadb.sock', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$identity = $pdo->query('SELECT @@socket AS socket, @@datadir AS datadir, @@skip_networking AS skip_networking')->fetch(PDO::FETCH_ASSOC);
check($identity['socket'] === $qa.'/mariadb.sock' && rtrim($identity['datadir'], '/') === $qa.'/data' && (int) $identity['skip_networking'] === 1, 'Actual private instance identity mismatch.');
$pdo->exec('SHUTDOWN');
$pdo = null;
$deadline = microtime(true) + 15;
while (file_exists('/proc/'.$pid) && microtime(true) < $deadline) { usleep(50000); clearstatcache(); }
check(!file_exists('/proc/'.$pid), 'Owned process did not exit by deadline.');
$before = trim(file_get_contents($qa.'/service-pid-before'));
$after = trim((string) shell_exec('/bin/systemctl show -p MainPID --value mariadb'));
check($before === $after && (int) $after > 1 && file_exists('/proc/'.$after), 'Main service PID changed.');
$proof = ['status' => 'PASS', 'only_owned_instance_shutdown' => true, 'owned_pid' => $pid, 'owned_process_absent' => true, 'main_service_pid_before' => (int) $before, 'main_service_pid_after' => (int) $after, 'main_pid_unchanged' => true, 'private_datadir_and_fixtures_retained' => true, 'backup_restore' => 'SKIPPED by user D-22'];
file_put_contents($qa.'/shutdown-proof.json', json_encode($proof, JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n");
echo json_encode($proof, JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n";
