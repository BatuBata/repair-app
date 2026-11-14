#!/usr/bin/env python3
"""Disposable mocked preflight/backup checks. Never contacts host, DB or ACME."""
import gzip
import json
import os
import re
from pathlib import Path
import shutil
import subprocess
import tempfile
import time

OPS = Path(__file__).resolve().parent
results = []

def record(name, expected, actual):
    assert actual == expected, (name, expected, actual)
    results.append({'case': name, 'expected': expected, 'actual': actual, 'status': 'PASS'})

with tempfile.TemporaryDirectory(prefix='repair-T024-check-') as tmp:
    root = Path(tmp).resolve()
    bin_dir = root / 'bin'
    bin_dir.mkdir()
    stub = '''#!/usr/bin/env python3
import json, os, pathlib, stat, sys
name=pathlib.Path(sys.argv[0]).name
with open(os.environ['CALLS'],'a') as f: f.write(json.dumps([name,*sys.argv[1:]])+'\\n')
if name=='stat':
    value=os.stat(sys.argv[-1])
    print(0 if sys.argv[2]=='%u' else oct(stat.S_IMODE(value.st_mode))[2:])
elif name in ('id','getent','systemctl'): pass
elif name=='nginx': print(os.environ.get('NGINX_TEXT','valid config'))
elif name=='apt-get':
    if sys.argv[1]!='-s': sys.exit(97)
    print(os.environ['APT_PLAN'])
else: sys.exit(97)
'''
    calls = root / 'calls'
    calls.write_text('')
    for name in ('stat', 'id', 'getent', 'systemctl', 'nginx', 'apt-get', 'install', 'update-alternatives', 'certbot'):
        file = bin_dir / name
        file.write_text(stub)
        file.chmod(0o700)
    bundle = root / 'root/repair-app-t024-bootstrap'
    bundle.mkdir(parents=True)
    for name in ('php-fpm.conf', 'nginx-http.conf', 'nginx-https.conf', 'logrotate.conf', 'renewal-hook.sh'):
        shutil.copyfile(OPS / name, bundle / name)
        (bundle / name).chmod(0o600)
    os_release = root / 'etc/os-release'
    os_release.parent.mkdir()
    os_release.write_text('ID=debian\nVERSION_ID=12\n')
    original = (OPS / 'bootstrap.sh').read_text()
    # Only adapt OS/root/absolute paths for this non-root macOS runner.
    adapted = original.replace('$EUID', '0')
    adapted = re.sub(r'/usr/sbin/nginx|/root|/etc|/var', lambda match: str(bin_dir / 'nginx') if match[0]=='/usr/sbin/nginx' else str(root / match[0].lstrip('/')), adapted)
    script = bundle / 'bootstrap.sh'
    script.write_text(adapted)
    script.chmod(0o600)
    bundle.chmod(0o700)
    base = dict(os.environ, PATH=f'{bin_dir}:{os.environ["PATH"]}', CALLS=str(calls),
                APT_PLAN='0 upgraded, 12 newly installed, 0 to remove and 172 not upgraded.')
    def guard(name, message, extra=None):
        calls.write_text('')
        outcome = subprocess.run(['bash', str(script), 'PREPARE:demo-repair-app'], env=dict(base, **(extra or {})), capture_output=True, text=True)
        assert outcome.returncode != 0 and message in outcome.stderr, (name, outcome.returncode, outcome.stdout, outcome.stderr)
        seen = [json.loads(x) for x in calls.read_text().splitlines()]
        mutations = [x for x in seen if x[0] in ('install', 'update-alternatives', 'certbot') or (x[0]=='apt-get' and x[1]!='-s')]
        record(name, [], mutations)
    result = subprocess.run(['bash', str(OPS/'bootstrap.sh')], capture_output=True, text=True)
    record('original script rejects missing confirmation', True, result.returncode != 0 and 'Usage:' in result.stderr)
    config = bundle/'php-fpm.conf'
    config.rename(bundle/'saved')
    config.symlink_to(bundle/'saved')
    guard('reject bundle symlink before mutations', 'no symlinks')
    config.unlink()
    (bundle/'saved').rename(config)
    config.chmod(0o620)
    guard('reject group writable bundle before mutations', 'not be writable')
    config.chmod(0o600)
    target = root/'var/www/demo-repair-app'
    target.mkdir(parents=True)
    guard('reject existing app target before mutations', 'Already exists:')
    target.rmdir()
    existing = root/'etc/nginx/sites-available/demo-repair-app'
    existing.parent.mkdir(parents=True)
    existing.touch()
    guard('reject existing vhost before mutations', 'Already exists:')
    existing.unlink()
    guard('reject domain already in loaded config', 'Domain already occurs', {'NGINX_TEXT':'server_name demo-repair-app.scannara.asia;'})
    guard('reject APT upgrade before alternatives mutation', 'APT plan would upgrade/remove', {'APT_PLAN':'1 upgraded, 12 newly installed, 0 to remove and 172 not upgraded.'})
    guard('reject unrelated new APT package', 'Unexpected new APT package', {'APT_PLAN':'Inst unrelated-service (1.0 Debian)\n0 upgraded, 13 newly installed, 0 to remove and 172 not upgraded.'})
    record('pin each existing alternative before install', True, original.index('update-alternatives --set') < original.index('DEBIAN_FRONTEND=noninteractive apt-get install') and 'groups=(php phar phar.phar)' in original)
    record('pending gate is never removed by bootstrap', False, 'rm -f -- /var/www/demo-repair-app/.deploy-pending' in original)

    dump = bin_dir/'mariadb-dump'
    dump.write_text('''#!/usr/bin/env python3
import json,os,sys
with open(os.environ['CALLS'],'a') as f: f.write(json.dumps(['mariadb-dump',*sys.argv[1:]])+'\\n')
if os.environ.get('FAIL')=='dump':
 print('private synthetic database error',file=sys.stderr);sys.exit(19)
print('CREATE TABLE synthetic (id INT); INSERT INTO synthetic VALUES (7);')
''')
    dump.chmod(0o700)
    for name in ('scp', 'ssh'):
        tool=bin_dir/name
        tool.write_text('''#!/usr/bin/env python3
import json,os,pathlib,sys
name=pathlib.Path(sys.argv[0]).name
with open(os.environ['CALLS'],'a') as f: f.write(json.dumps([name,*sys.argv[1:]])+'\\n')
if os.environ.get('FAIL')==name: print('private synthetic transfer error',file=sys.stderr);sys.exit(19)
''')
        tool.chmod(0o700)
    # Backup uses platform stat fallback; remove mock stat from its PATH.
    (bin_dir/'stat').unlink()
    cnf=root/'client.cnf'
    cnf.write_text('[client]\nuser=synthetic\npassword=synthetic-secret\n')
    cnf.chmod(0o600)
    for case in ('dump', 'scp', 'ssh', 'pending', 'success'):
        directory=root/f'backup-{case}'
        directory.mkdir(mode=0o700)
        old=directory/'demo_repair_app-20200101T030000Z-ABC123.sql.gz'
        old.write_bytes(gzip.compress(b'old'))
        os.utime(old, (time.time()-15*86400,)*2)
        calls.write_text('')
        remote=['-','-'] if case=='pending' else ['offsite','/private/backups']
        outcome=subprocess.run(['bash', str(OPS/'backup-mariadb.sh'), str(cnf), 'demo_repair_app', str(directory), *remote], env=dict(base, FAIL=case), capture_output=True,text=True)
        record(f'backup {case} exit success', case=='success', outcome.returncode==0)
        seen=[json.loads(x) for x in calls.read_text().splitlines()]
        dump_call=next(x for x in seen if x[0]=='mariadb-dump')
        record(f'backup {case} MariaDB compatible flags', True, dump_call[1]==f'--defaults-file={cnf}' and '--single-transaction' in dump_call and '--triggers' in dump_call and '--set-gtid-purged=OFF' not in dump_call and '--column-statistics=0' not in dump_call and '--databases' not in dump_call)
        record(f'backup {case} old archive retained', case!='success', old.exists())
        record(f'backup {case} secret/error output hidden', False, any(x in outcome.stdout+outcome.stderr+calls.read_text() for x in ('synthetic-secret','private synthetic')))
        record(f'backup {case} lock/temp cleaned', [], list(directory.glob('.backup-*'))+list(directory.glob('.work-*')))
        if case=='pending':
            record('pending saves valid local archive but stays nonzero', 2, len(list(directory.glob('*.sql.gz'))))
            record('pending never transfers', False, any(x[0] in ('scp','ssh') for x in seen))
    directory=root/'backup-invalid'
    directory.mkdir(mode=0o700)
    for case, db, mode, alias, dest in [('invalid DB','bad*',0o600,'-','-'),('unsafe client mode','demo_repair_app',0o644,'-','-'),('unsafe remote path','demo_repair_app',0o600,'offsite','/bad/../path')]:
        cnf.chmod(mode);calls.write_text('')
        outcome=subprocess.run(['bash',str(OPS/'backup-mariadb.sh'),str(cnf),db,str(directory),alias,dest],env=base,capture_output=True,text=True)
        record(f'backup rejects {case} without commands', True, outcome.returncode!=0 and calls.read_text()=='')
    cnf.chmod(0o600)
    (directory/'.backup-demo_repair_app.lock').mkdir()
    calls.write_text('')
    outcome=subprocess.run(['bash',str(OPS/'backup-mariadb.sh'),str(cnf),'demo_repair_app',str(directory),'-','-'],env=base,capture_output=True,text=True)
    record('backup rejects existing lock before dump', True, outcome.returncode!=0 and calls.read_text()=='')

(OPS/'checks.json').write_text(json.dumps({'status':'PASS','checks':len(results),'cases':results,'limits':'Mock preflight + dump/SSH tools only; no real Debian/Nginx/FPM/Certbot/MariaDB/offsite action tested.'}, indent=2)+'\n')
print(f'PASS {len(results)} checks (mocked host/DB/SSH, no remote actions)')
