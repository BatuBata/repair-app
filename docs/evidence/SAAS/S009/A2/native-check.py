"""Independent S009/A2: exact native client, candidate backup/restore, and fail-closed guards."""
import gzip
import hashlib
import json
import os
from pathlib import Path
import secrets
import shutil
import subprocess
import tempfile
import time

OUT = Path('docs/evidence/SAAS/S009/A2')
RUNTIME = Path(tempfile.mkdtemp(prefix='saas-s009-a2-', dir='/private/tmp'))
RUNTIME.chmod(0o700)
CONTAINER = 'saas-s009-a2-qa'
IMAGE = 'mariadb:10.11.14'
DIGEST = 'sha256:dbe56e20372fc6d6b8e0e396866ba89c4c7f128c38c4f59aaa54d957db95790c'
DB_SOURCE, DB_TARGET = 'saas_s009_a2_source', 'saas_s009_a2_scratch'
ROOT_PASSWORD, OPS_PASSWORD = secrets.token_urlsafe(30), secrets.token_urlsafe(30)
created = False
result = {'verdict': 'running', 'commands': [], 'runtime': str(RUNTIME)}

def run(args, expected=0, data=None, env=None):
    process = subprocess.run([str(x) for x in args], input=data, env=env, capture_output=True, timeout=120)
    if expected is not None:
        assert process.returncode == expected, (args[0], process.returncode, process.stderr.decode(errors='replace')[:300])
    return process

def docker(*args, **kwargs):
    return run(['docker', '--context', 'colima', *args], **kwargs)

def private(name, contents):
    file = RUNTIME / name
    file.write_text(contents)
    file.chmod(0o600)
    return file

def sql(database, statement):
    return docker('exec', '-i', CONTAINER, 'mariadb', '--defaults-file='+str(RUNTIME/'root.cnf'),
                  '--batch', '--raw', '--skip-column-names', '--database='+database,
                  data=statement.encode()).stdout

def snapshot(database):
    statements = [
        "SELECT JSON_OBJECT('id',id,'amount',amount,'label',label,'payload',HEX(payload),'stamp',CAST(stamp AS CHAR)) FROM qa_parent ORDER BY id;",
        "SELECT JSON_OBJECT('id',id,'parent_id',parent_id,'note',note) FROM qa_child ORDER BY id;",
        'SHOW CREATE TABLE qa_parent;', 'SHOW CREATE TABLE qa_child;',
        'SELECT TRIGGER_NAME,ACTION_TIMING,EVENT_MANIPULATION,EVENT_OBJECT_TABLE,ACTION_STATEMENT,DEFINER,SQL_MODE FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() ORDER BY TRIGGER_NAME;'
    ]
    return [hashlib.sha256(sql(database, statement)).hexdigest() for statement in statements]

try:
    assert docker('container', 'inspect', CONTAINER, expected=None).returncode != 0, 'Refuse existing container'
    digest = json.loads(docker('image', 'inspect', IMAGE, '--format', '{{json .RepoDigests}}').stdout)
    assert any(x.endswith('@'+DIGEST) for x in digest), digest
    env_file = private('container.env', 'MARIADB_ROOT_PASSWORD='+ROOT_PASSWORD+'\n')
    docker('run', '-d', '--pull=never', '--name', CONTAINER, '--label', 'qa.task=S009-A2',
           '--network', 'none', '--tmpfs', '/var/lib/mysql:rw,nosuid,noexec,size=512m',
           '--env-file', env_file, IMAGE)
    created = True
    for attempt in range(30):
        if docker('exec', CONTAINER, 'healthcheck.sh', '--connect', '--innodb_initialized', expected=None).returncode == 0:
            break
        time.sleep(1)
    else:
        raise RuntimeError('Container not healthy after30 seconds')
    docker('exec', CONTAINER, 'mkdir', '-p', RUNTIME)
    root_cnf = private('root.cnf', '[client]\nuser=root\npassword="'+ROOT_PASSWORD+'"\nprotocol=socket\n')
    client_cnf = private('client.cnf', '[client]\nuser=qa_ops\npassword="'+OPS_PASSWORD+'"\nprotocol=socket\n')
    docker('cp', root_cnf, CONTAINER+':'+str(root_cnf))
    version = sql('mysql', 'SELECT VERSION();').decode().strip()
    assert version.startswith('10.11.14-MariaDB'), version
    sql('mysql', f"""CREATE DATABASE {DB_SOURCE} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE {DB_TARGET} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER qa_ops@localhost IDENTIFIED BY '{OPS_PASSWORD}';
GRANT SELECT,SHOW VIEW,TRIGGER ON {DB_SOURCE}.* TO qa_ops@localhost;
GRANT ALL PRIVILEGES ON {DB_TARGET}.* TO qa_ops@localhost;
USE {DB_SOURCE};
CREATE TABLE qa_parent(id BIGINT UNSIGNED PRIMARY KEY,amount BIGINT NOT NULL,label VARCHAR(90) NULL,payload VARBINARY(20),stamp DATETIME NOT NULL) ENGINE=InnoDB;
CREATE TABLE qa_child(id INT PRIMARY KEY,parent_id BIGINT UNSIGNED NOT NULL,note TEXT,CONSTRAINT qa_fk FOREIGN KEY(parent_id) REFERENCES qa_parent(id)) ENGINE=InnoDB;
CREATE DEFINER=qa_ops@localhost TRIGGER qa_marker BEFORE INSERT ON qa_parent FOR EACH ROW DO 0;
INSERT INTO qa_parent VALUES(7,125000,'Anugerah sintetis',0x0001FEFF,'2026-11-17 11:00:00'),(8,-50,NULL,0x00,'2026-10-31 08:00:00');
INSERT INTO qa_child VALUES(11,7,'UTF8 é — fixture'),(12,8,NULL);
""")
    baseline = snapshot(DB_SOURCE)
    transport = RUNTIME / 'bin'
    transport.mkdir(mode=0o700)
    calls = RUNTIME / 'calls.jsonl'
    wrapper = '''#!/usr/bin/env python3
import json,os,pathlib,subprocess,sys
r=pathlib.Path(__file__).resolve().parent.parent
name=pathlib.Path(sys.argv[0]).name
with (r/'calls.jsonl').open('a') as f:f.write(json.dumps([name,*sys.argv[1:]])+'\\n')
if sys.argv[1:2] and sys.argv[1].startswith('--defaults-file='):
 cnf=sys.argv[1].split('=',1)[1]
 subprocess.run(['docker','--context','colima','cp',cnf,'saas-s009-a2-qa:'+cnf],check=True,capture_output=True)
binary='mariadb-dump' if name=='mysqldump' else 'mariadb'
os.execvp('docker',['docker','--context','colima','exec','-i','saas-s009-a2-qa',binary,*sys.argv[1:]])
'''
    for name in ('mysql', 'mysqldump'):
        path = transport / name
        path.write_text(wrapper)
        path.chmod(0o700)
    (OUT/'client-transport.py').write_text(wrapper)
    env = dict(os.environ, PATH=str(transport)+os.pathsep+os.environ['PATH'])
    backup = RUNTIME/'backup'
    backup.mkdir(mode=0o700)
    old = backup/(DB_SOURCE+'-20000101T000000Z-ABC123.sql.gz')
    old.write_bytes(gzip.compress(b'old synthetic keep'))
    os.utime(old, (time.time()-20*86400,)*2)
    command = ['bash', 'deploy/backup.sh', client_cnf, DB_SOURCE, backup, '-', '-']
    process = run(command, expected=1, env=env)
    expected_message = 'Local archive saved; Q-04 off-VPS destination missing; retention skipped (NOT a successful daily backup)'
    assert process.stderr.decode().strip() == expected_message
    archives = [p for p in backup.glob('*.sql.gz') if p != old]
    assert len(archives) == 1
    archive = archives[0]
    raw = gzip.decompress(archive.read_bytes())
    assert b'CREATE TABLE `qa_parent`' in raw and b'TRIGGER' in raw and b'0x0001FEFF' in raw
    assert b'CREATE DATABASE' not in raw and b'\nUSE ' not in raw
    assert old.exists() and not list(backup.glob('.work-*')) and not list(backup.glob('.backup-*'))
    copied = RUNTIME/'copied.sql.gz'
    shutil.copyfile(archive, copied)
    checksum = hashlib.sha256(archive.read_bytes()).hexdigest()
    assert hashlib.sha256(copied.read_bytes()).hexdigest() == checksum
    restore_command = ['bash', 'deploy/restore.sh', client_cnf, DB_TARGET, copied, 'RESTORE:'+DB_TARGET]
    restore = run(restore_command, env=env)
    restored = snapshot(DB_TARGET)
    assert baseline == restored, 'Restored fields/schema/FK/trigger mismatch'
    native_calls = [json.loads(x) for x in calls.read_text().splitlines()]
    probe, dump, imp = native_calls
    assert probe == ['mysqldump', '--no-defaults', '--version']
    assert dump[1] == '--defaults-file='+str(client_cnf) and dump[-1] == DB_SOURCE
    assert all(x in dump for x in ('--single-transaction','--quick','--no-tablespaces','--hex-blob','--triggers'))
    assert not any(x in dump for x in ('--set-gtid-purged=OFF','--column-statistics=0','--databases'))
    assert imp[-1] == '--database='+DB_TARGET
    result['native'] = {'server': version, 'image_digest': DIGEST, 'platform': docker('image','inspect',IMAGE,'--format','{{.Os}}/{{.Architecture}}').stdout.decode().strip(), 'dump_version': docker('exec',CONTAINER,'mariadb-dump','--version').stdout.decode().strip(), 'client_version': docker('exec',CONTAINER,'mariadb','--version').stdout.decode().strip(), 'backup_exit':process.returncode,'backup_stderr':process.stderr.decode().strip(),'archive_bytes':archive.stat().st_size,'sql_bytes':len(raw),'gzip_valid':True,'archive_sha256':checksum,'copied_sha256_equal':True,'restored_fields_schema_fk_trigger_equal':True,'snapshot_sha256':baseline,'explicit_target_no_database_or_use':True,'retention_skipped_old_archive_retained':True,'restore_exit':restore.returncode,'restore_stdout':restore.stdout.decode().strip(),'actual_calls':native_calls,'network':'none','storage':'dedicated tmpfs; no port or shared mount','account_scope':'qa_ops SELECT/SHOW VIEW/TRIGGER on source; ALL on scratch only; no global grants'}
    fake = RUNTIME/'fake'
    fake.mkdir(mode=0o700)
    fake_script = fake/'mysqldump'
    fake_script.write_text('''#!/usr/bin/env python3
import json,os,pathlib,sys
with open(os.environ['FAKE_CALLS'],'a') as f:f.write(json.dumps(sys.argv[1:])+'\\n')
if sys.argv[1:]!=['--no-defaults','--version']:sys.exit(99)
print(os.environ.get('FAKE_VERSION',''))
sys.exit(int(os.environ.get('FAKE_EXIT','0')))
''')
    fake_script.chmod(0o700)
    guarded = []
    for label, text, code in [('unknown','unknown client',0),('empty','',0),('unsupported-mariadb','mariadb-dump Ver 10.19 Distrib 10.10.14-MariaDB, for Linux',0),('unsupported-mysql','mysqldump Ver 9.0.0 for Linux (MySQL Community Server)',0),('ambiguous','mysqldump Ver 8.0.31 MySQL MariaDB',0),('probe-error','mysqldump Ver 8.0.31 MySQL',17)]:
        callfile = RUNTIME/(label+'.calls')
        before = sorted(backup.iterdir())
        case_env = dict(env, PATH=str(fake)+os.pathsep+os.environ['PATH'], FAKE_CALLS=str(callfile), FAKE_VERSION=text, FAKE_EXIT=str(code))
        case = run(command, expected=1, env=case_env)
        assert sorted(backup.iterdir()) == before, label
        assert [json.loads(x) for x in callfile.read_text().splitlines()] == [['--no-defaults','--version']], label
        guarded.append({'case':label,'exit':case.returncode,'stderr':case.stderr.decode().strip(),'dump_not_called':True,'backup_directory_unchanged':True})
    result['independent_fail_closed'] = guarded
    result['commands'] = [[str(x) for x in command],[str(x) for x in restore_command]]
    result['verdict'] = 'pass'
except Exception as error:
    result['verdict'] = 'fail'
    result['error'] = str(error)
finally:
    if created:
        cleanup = docker('rm','-f',CONTAINER,expected=None)
        assert cleanup.returncode == 0, 'Own container cleanup failed'
    absent = docker('container','inspect',CONTAINER,expected=None).returncode != 0
    assert absent
    result['cleanup'] = {'own_container_absent':absent,'tmpfs_databases_removed':True,'shared_colima_image_retained':True,'private_runtime_removed':False}
    for file in OUT.iterdir():
        if file.is_file():
            assert ROOT_PASSWORD.encode() not in file.read_bytes() and OPS_PASSWORD.encode() not in file.read_bytes(), 'Secret found in evidence'
    serialized = json.dumps(result,indent=2)
    assert ROOT_PASSWORD not in serialized and OPS_PASSWORD not in serialized
    shutil.rmtree(RUNTIME)
    result['cleanup']['private_runtime_removed'] = not RUNTIME.exists()
    result['secret_redaction'] = 'two actual private credential values absent from evidence/result; runtime/config/container removed'
    (OUT/'native-result.json').write_text(json.dumps(result,indent=2)+'\n')
    print(json.dumps(result,indent=2))
if result['verdict'] != 'pass':
    raise SystemExit(1)
